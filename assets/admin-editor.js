(function (wp) {
    if (!wp || !wp.blocks || !wp.blockEditor || !wp.element || !wp.data || !wp.domReady) {
        return;
    }

    wp.domReady(function () {
        var mountNode = document.getElementById('bve-lu-block-editor');
        var textarea = document.getElementById('content');
        var form = textarea ? textarea.closest('form') : null;

        if (!mountNode || !textarea || !form) {
            return;
        }

        var createElement = wp.element.createElement;
        var useState = wp.element.useState;
        var useEffect = wp.element.useEffect;
        var BlockEditorProvider = wp.blockEditor.BlockEditorProvider;
        var BlockList = wp.blockEditor.BlockList;
        var WritingFlow = wp.blockEditor.WritingFlow;
        var ObserveTyping = wp.blockEditor.ObserveTyping;
        var BlockTools = wp.blockEditor.BlockTools;
        var VisualEditor = wp.blockEditor.VisualEditor;
        var SlotFillProvider = wp.components.SlotFillProvider;
        var Popover = wp.components.Popover;
        var parse = wp.blocks.parse;
        var serialize = wp.blocks.serialize;

        var initialBlocks = parse(textarea.value || '');

        function EditorApp() {
            var _wp$element$useState = useState(initialBlocks),
                blocks = _wp$element$useState[0],
                setBlocks = _wp$element$useState[1];

            useEffect(function () {
                textarea.value = serialize(blocks);
            }, [blocks]);

            return createElement(
                SlotFillProvider,
                {},
                createElement(
                    BlockEditorProvider,
                    {
                        value: blocks,
                        onInput: setBlocks,
                        onChange: setBlocks
                    },
                    createElement(
                        BlockTools,
                        {},
                        createElement(
                            WritingFlow,
                            {},
                            createElement(
                                ObserveTyping,
                                {},
                                createElement(
                                    VisualEditor,
                                    {},
                                    createElement(BlockList, {})
                                )
                            )
                        )
                    ),
                    createElement(Popover.Slot, {})
                )
            );
        }

        wp.element.render(createElement(EditorApp), mountNode);

        form.addEventListener('submit', function () {
            var currentBlocks = wp.data.select('core/block-editor').getBlocks();
            textarea.value = serialize(currentBlocks);
        });
    });
})(window.wp);
