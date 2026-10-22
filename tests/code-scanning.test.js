const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const root = path.join(__dirname, '..');

function clickEmbed(script, embedUrl, missingPlaceholder = false) {
    const errors = [];
    const frames = [];
    const replacements = [];
    const cookies = [];
    let onReady;
    let onClick;
    let prevented = false;
    const placeholder = {
        getAttribute: () => embedUrl,
        parentNode: {
            replaceChild: (frame, previous) => replacements.push({ frame, previous })
        }
    };
    const button = {
        getAttribute: () => 'youtube',
        closest: () => missingPlaceholder ? null : placeholder,
        addEventListener: (event, handler) => { onClick = handler; }
    };
    const document = {
        baseURI: 'https://example.com/posts/current',
        addEventListener: (event, handler) => { onReady = handler; },
        querySelector: () => null,
        querySelectorAll: (selector) =>
            selector === '.psdsgvo-embed-consent-btn' ? [button] : [],
        createElement: (tag) => {
            assert.equal(tag, 'iframe');
            const frame = {};
            frames.push(frame);
            return frame;
        },
        set cookie(value) { cookies.push(value); }
    };

    vm.runInNewContext(fs.readFileSync(path.join(root, script), 'utf8'), {
        window: {},
        document,
        psdsgvoData: { path: '/' },
        URL,
        console: { error: (...args) => errors.push(args) }
    }, { filename: script });
    onReady();
    onClick.call(button, { preventDefault: () => { prevented = true; } });
    assert.equal(prevented, true);
    return { errors, frames, replacements, cookies, placeholder };
}

for (const script of ['assets/js/front.js', 'assets/js/front.min.js']) {
    test(`${script}: loads valid embed URLs after consent`, () => {
        for (const [input, expected] of [
            ['https://www.youtube.com/embed/video?a=1&b=2', 'https://www.youtube.com/embed/video?a=1&b=2'],
            ['http://player.vimeo.com/video/123', 'http://player.vimeo.com/video/123'],
            ['//www.youtube.com/embed/video', 'https://www.youtube.com/embed/video'],
            ['/embed/video', 'https://example.com/embed/video'],
            ['../embed/video', 'https://example.com/embed/video']
        ]) {
            const result = clickEmbed(script, input);
            assert.equal(result.errors.length, 0, input);
            assert.equal(result.frames.length, 1, input);
            assert.equal(result.frames[0].src, expected);
            assert.equal(result.frames[0].width, '100%');
            assert.equal(result.frames[0].height, '400');
            assert.equal(result.frames[0].frameBorder, '0');
            assert.equal(result.frames[0].allowFullscreen, true);
            assert.equal(result.replacements.length, 1);
            assert.equal(result.replacements[0].previous, result.placeholder);
            assert.equal(result.replacements[0].frame, result.frames[0]);
            assert.equal(result.cookies.length, 1);
            assert.match(result.cookies[0], /^psdsgvo_embed_youtube=1; expires=/);
            assert.match(result.cookies[0], /; path=\/$/);
        }
    });

    test(`${script}: rejects unsafe, malformed and missing URLs before consent`, () => {
        for (const input of [
            'javascript:alert(1)',
            'JaVaScRiPt:alert(1)',
            ' \tjavascript:alert(1)',
            'java\nscript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'vbscript:msgbox(1)',
            'file:///etc/passwd',
            'blob:https://example.com/id',
            'ftp://example.com/video',
            'https://[invalid',
            '',
            '   ',
            null
        ]) {
            const result = clickEmbed(script, input);
            assert.equal(result.errors.length, 1, String(input));
            assert.equal(result.frames.length, 0, String(input));
            assert.equal(result.replacements.length, 0, String(input));
            assert.equal(result.cookies.length, 0, String(input));
        }
        const result = clickEmbed(script, null, true);
        assert.equal(result.errors.length, 1);
        assert.equal(result.cookies.length, 0);
        assert.equal(result.frames.length, 0);
    });
}

test('CodeMirror: recognizes whitespace-delimited line classes without duplicates', () => {
    const source = fs.readFileSync(path.join(root, 'assets/vendor/codemirror/codemirror.js'), 'utf8');
    const start = source.indexOf('function extractLineClasses(');
    const end = source.indexOf('// Run the given mode', start);
    assert.ok(start >= 0 && end > start);
    const context = {};
    vm.runInNewContext(source.slice(start, end), context);

    for (const [token, property] of [
        ['line-selected', 'textClass'],
        ['line-background-selected', 'bgClass']
    ]) {
        for (const classes of ['selected', 'other selected', 'selected other', 'other\tselected\nlast']) {
            const output = { [property]: classes };
            assert.equal(context.extractLineClasses(`keyword ${token}`, output), 'keyword');
            assert.equal(output[property], classes);
        }
        const output = { [property]: 'unselected selected-other' };
        context.extractLineClasses(token, output);
        assert.equal(output[property], 'unselected selected-other selected');
        context.extractLineClasses(token, output);
        assert.equal(output[property], 'unselected selected-other selected');
        const empty = {};
        context.extractLineClasses(`${token} ${token}`, empty);
        assert.equal(empty[property], 'selected');
    }
});
