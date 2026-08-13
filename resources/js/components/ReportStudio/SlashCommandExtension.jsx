import { Extension } from '@tiptap/core';
import Suggestion from '@tiptap/suggestion';
import { ReactRenderer } from '@tiptap/react';
import { PluginKey } from '@tiptap/pm/state';
import tippy from 'tippy.js';
import SlashMenuList from './SlashMenuList';

const SlashPluginKey = new PluginKey('slashCommandSuggestion');

const getTippyInstance = (inst) => {
  if (!inst) return null;
  return Array.isArray(inst) ? inst[0] : inst;
};

export const SlashCommandExtension = Extension.create({
  name: 'slashCommand',

  addOptions() {
    return {
      editorId: null,
      onOpenPromoteModal: null,
      onOpenTodoModal: null,
    };
  },

  addProseMirrorPlugins() {
    const extensionSelf = this;
    const editorId = this.options.editorId;

    return [
      Suggestion({
        pluginKey: SlashPluginKey,
        editor: this.editor,
        char: '/',
        startOfLine: false,

        items: ({ query }) => {
          console.log('Slash Triggered:', query);
          const lowerQuery = query.toLowerCase();
          const items = [
            { id: 'promote_action', title: '📌 Promote Action Modal', subtitle: 'Open form to create high-priority promote action' },
            { id: 'todo_task', title: '☑️ To-Do Task Modal', subtitle: 'Open form to assign a new audit to-do task' }
          ];

          if (!lowerQuery) return items;
          return items.filter(item =>
            item.id.includes(lowerQuery) ||
            item.title.toLowerCase().includes(lowerQuery) ||
            'create'.includes(lowerQuery)
          );
        },

        render: () => {
          let component;
          let popup;
          let latestRange;
          let currentEditor;

          const executeCommand = (item) => {
            const actionId = typeof item === 'object' ? item.id : item;
            console.log('[STEP 2: SlashCommandExtension] Command executed!', { actionId, editorId, options: extensionSelf.options });

            // Delete the typed slash command text (/ or /create)
            try {
              if (currentEditor && latestRange) {
                console.log('[STEP 2: SlashCommandExtension] Deleting slash command range:', latestRange);
                currentEditor.chain().focus().deleteRange(latestRange).run();
              }
            } catch (err) {
              console.warn('[STEP 2: SlashCommandExtension] deleteRange error:', err);
            }

            // 1. Direct options callback trigger
            if (actionId === 'promote_action' && typeof extensionSelf.options?.onOpenPromoteModal === 'function') {
              console.log('[STEP 2: SlashCommandExtension] Calling onOpenPromoteModal via options...');
              extensionSelf.options.onOpenPromoteModal();
            } else if (actionId === 'todo_task' && typeof extensionSelf.options?.onOpenTodoModal === 'function') {
              console.log('[STEP 2: SlashCommandExtension] Calling onOpenTodoModal via options...');
              extensionSelf.options.onOpenTodoModal();
            } else {
              console.log('[STEP 2: SlashCommandExtension] Options callback check:', extensionSelf.options);
            }

            // 2. Global window event trigger for guaranteed delivery
            console.log('[STEP 2: SlashCommandExtension] Dispatching window event: tiptap-slash-command', { actionId, editorId });
            window.dispatchEvent(
              new CustomEvent('tiptap-slash-command', {
                detail: { action: actionId, editorId }
              })
            );
          };

          return {
            onStart: (props) => {
              latestRange = props.range;
              currentEditor = props.editor;

              component = new ReactRenderer(SlashMenuList, {
                props: {
                  ...props,
                  command: executeCommand,
                },
                editor: props.editor,
              });

              if (!props.clientRect) return;

              popup = tippy(document.body, {
                getReferenceClientRect: props.clientRect,
                appendTo: () => document.body,
                content: component.element,
                showOnCreate: true,
                interactive: true,
                trigger: 'manual',
                placement: 'bottom-start',
                zIndex: 99999,
              });
            },

            onUpdate(props) {
              latestRange = props.range;
              currentEditor = props.editor;

              // Crucial: preserve custom command handler so Tippy/Tiptap suggestion props update does not overwrite it!
              component?.updateProps({
                ...props,
                command: executeCommand,
              });

              if (!props.clientRect) return;

              const instance = getTippyInstance(popup);
              instance?.setProps({
                getReferenceClientRect: props.clientRect,
              });
            },

            onKeyDown(props) {
              const instance = getTippyInstance(popup);
              if (props.event.key === 'Escape') {
                instance?.hide();
                return true;
              }
              return component?.ref?.onKeyDown(props) || false;
            },

            onExit() {
              const instance = getTippyInstance(popup);
              instance?.destroy();
              component?.destroy();
            },
          };
        },
      }),
    ];
  },
});

export default SlashCommandExtension;
