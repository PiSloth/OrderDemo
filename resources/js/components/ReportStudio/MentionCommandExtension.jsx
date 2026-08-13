import { Extension } from '@tiptap/core';
import Suggestion from '@tiptap/suggestion';
import { ReactRenderer } from '@tiptap/react';
import { PluginKey } from '@tiptap/pm/state';
import tippy from 'tippy.js';
import MentionMenuList from './MentionMenuList';

const MentionPluginKey = new PluginKey('mentionCommandSuggestion');

const getTippyInstance = (inst) => {
  if (!inst) return null;
  return Array.isArray(inst) ? inst[0] : inst;
};

// Default sample tasks and promote actions repository for @ mention search
const DEFAULT_MENTION_ITEMS = [
  {
    id: 'mention-todo-1',
    type: 'todo_task',
    title: 'Clean Vault Area & Verify Main Safe Cash Drawers',
    subtitle: 'In Progress',
    category: 'Audit & Compliance',
    status: 'In Progress',
  },
  {
    id: 'mention-todo-2',
    type: 'todo_task',
    title: 'Odoo ERP System Data Entry: Confirm QC Verified Stock List',
    subtitle: 'In Progress',
    category: 'IT & Systems',
    status: 'In Progress',
  },
  {
    id: 'mention-todo-3',
    type: 'todo_task',
    title: 'Pawn Ticket Serial Audit: Branch 1 Counter Review',
    subtitle: 'Pending',
    category: 'Audit & Compliance',
    status: 'Pending',
  },
  {
    id: 'mention-todo-4',
    type: 'todo_task',
    title: 'Perform Petty Cash Reconciliation & Dual Sign-off Review',
    subtitle: 'Completed',
    category: 'Finance & Treasury',
    status: 'Completed',
  },
  {
    id: 'mention-todo-5',
    type: 'todo_task',
    title: 'Security Audit: Review Access Logs & Vault Surveillance Footage',
    subtitle: 'Under Review',
    category: 'Audit & Compliance',
    status: 'Under Review',
  },
  {
    id: 'mention-todo-6',
    type: 'todo_task',
    title: 'Gold Scale Calibration & Standard Weight Certification',
    subtitle: 'Pending',
    category: 'Store Operations',
    status: 'Pending',
  },
  {
    id: 'mention-promote-1',
    type: 'promote_action',
    title: 'High-Priority Vault Security Action Plan',
    subtitle: '2026-08-01 - 2026-08-15',
    category: 'Promote Action',
    start_date: '2026-08-01',
    end_date: '2026-08-15',
  },
  {
    id: 'mention-promote-2',
    type: 'promote_action',
    title: 'Financial Petty Cash Compliance Review',
    subtitle: '2026-08-05 - 2026-08-20',
    category: 'Promote Action',
    start_date: '2026-08-05',
    end_date: '2026-08-20',
  },
];

export const MentionCommandExtension = Extension.create({
  name: 'mentionCommand',

  addOptions() {
    return {
      editorId: null,
      customItems: [],
    };
  },

  addProseMirrorPlugins() {
    const editorId = this.options.editorId;
    const customItems = this.options.customItems || [];

    return [
      Suggestion({
        pluginKey: MentionPluginKey,
        editor: this.editor,
        char: '@',
        startOfLine: false,

        items: ({ query }) => {
          console.log('[MentionCommandExtension] Triggered query:', query);
          const lowerQuery = query.toLowerCase();
          const allItems = [...customItems, ...DEFAULT_MENTION_ITEMS];

          // Filter out duplicates by id
          const seen = new Set();
          const uniqueItems = allItems.filter(item => {
            const k = item.id || item.title;
            if (seen.has(k)) return false;
            seen.add(k);
            return true;
          });

          if (!lowerQuery) return uniqueItems;
          return uniqueItems.filter(item =>
            item.title.toLowerCase().includes(lowerQuery) ||
            (item.category && item.category.toLowerCase().includes(lowerQuery)) ||
            (item.subtitle && item.subtitle.toLowerCase().includes(lowerQuery))
          );
        },

        render: () => {
          let component;
          let popup;
          let latestRange;
          let currentEditor;

          const executeCommand = (item) => {
            console.log('[MentionCommandExtension] Selected item:', item, 'editorId:', editorId);

            // Replace the typed @query text range with an inline citation tag
            try {
              if (currentEditor && latestRange) {
                currentEditor.chain().focus().deleteRange(latestRange).run();
              }
            } catch (err) {
              console.warn('[MentionCommandExtension] deleteRange error:', err);
            }

            if (item.type === 'todo_task') {
              const name = (item.title || item.task || '').trim();
              const status = item.status || item.subtitle || 'In Progress';
              currentEditor.chain().focus().insertContent(` [todo_task: ${name} | ${status}] `).run();
            } else if (item.type === 'promote_action') {
              const name = (item.title || item.action_name || '').trim();
              const start = item.start_date || '2026-08-01';
              const end = item.end_date || '2026-08-15';
              currentEditor.chain().focus().insertContent(` [promote_action: ${name} | ${start} - ${end}] `).run();
            }

            // Dispatch global window event so the report block item glues this task/action to the top header
            window.dispatchEvent(
              new CustomEvent('tiptap-mention-selected', {
                detail: { item, editorId }
              })
            );
          };

          return {
            onStart: (props) => {
              latestRange = props.range;
              currentEditor = props.editor;

              component = new ReactRenderer(MentionMenuList, {
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

export default MentionCommandExtension;
