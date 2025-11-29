@php
    $id = $getId();
    $statePath = $getStatePath();
@endphp

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="mentionEditor_{{ str_replace('.', '_', $id) }}()"
        class="relative"
        wire:ignore.self
    >
        {{-- Mention Suggestions Dropdown --}}
        <div
            x-show="showSuggestions"
            x-cloak
            x-transition
            class="absolute z-50 w-64 mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-48 overflow-y-auto"
            style="top: 60px; left: 10px;"
        >
            <template x-if="loading">
                <div class="p-3 text-center text-gray-500">
                    <svg class="animate-spin h-5 w-5 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>
            <template x-if="!loading && suggestions.length === 0 && searchQuery.length > 0">
                <div class="p-3 text-center text-gray-500 text-sm">
                    No se encontraron usuarios
                </div>
            </template>
            <template x-for="(user, index) in suggestions" :key="user.id">
                <button
                    type="button"
                    @click="selectUser(user)"
                    @mouseenter="selectedIndex = index"
                    :class="{'bg-primary-100 dark:bg-primary-900': selectedIndex === index}"
                    class="w-full px-3 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-2 text-sm"
                >
                    <span class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-xs font-medium" x-text="user.name.charAt(0).toUpperCase()"></span>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white" x-text="user.name"></div>
                        <div class="text-xs text-gray-500" x-text="user.email"></div>
                    </div>
                </button>
            </template>
        </div>

        {{-- Original RichEditor --}}
        <div x-ref="editorWrapper">
            @include('filament-forms::components.rich-editor')
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('mentionEditor_{{ str_replace('.', '_', $id) }}', () => ({
                showSuggestions: false,
                suggestions: [],
                loading: false,
                selectedIndex: 0,
                searchQuery: '',
                mentionStart: null,
                trixEditor: null,

                init() {
                    this.$nextTick(() => {
                        const wrapper = this.$refs.editorWrapper;
                        if (wrapper) {
                            const trixEl = wrapper.querySelector('trix-editor');
                            if (trixEl) {
                                this.trixEditor = trixEl;
                                trixEl.addEventListener('trix-change', () => this.handleInput());
                                trixEl.addEventListener('keydown', (e) => this.handleKeydown(e));
                            }
                        }
                    });

                    this.$watch('searchQuery', (value) => {
                        if (value.length > 0) {
                            this.fetchUsers(value);
                        } else {
                            this.suggestions = [];
                        }
                    });
                },

                handleKeydown(e) {
                    if (!this.showSuggestions) return;

                    if (e.key === 'Escape') {
                        this.showSuggestions = false;
                        e.preventDefault();
                    } else if (e.key === 'ArrowUp') {
                        this.selectedIndex = Math.max(0, this.selectedIndex - 1);
                        e.preventDefault();
                    } else if (e.key === 'ArrowDown') {
                        this.selectedIndex = Math.min(this.suggestions.length - 1, this.selectedIndex + 1);
                        e.preventDefault();
                    } else if ((e.key === 'Enter' || e.key === 'Tab') && this.suggestions.length > 0) {
                        this.selectUser(this.suggestions[this.selectedIndex]);
                        e.preventDefault();
                    }
                },

                handleInput() {
                    if (!this.trixEditor || !this.trixEditor.editor) return;

                    const editor = this.trixEditor.editor;
                    const position = editor.getPosition();
                    const document = editor.getDocument();
                    const text = document.toString();

                    const textBeforeCursor = text.substring(0, position);
                    const lastAtIndex = textBeforeCursor.lastIndexOf('@');

                    if (lastAtIndex !== -1) {
                        const textAfterAt = textBeforeCursor.substring(lastAtIndex + 1);
                        if (!textAfterAt.includes(' ') && !textAfterAt.includes('\n')) {
                            this.searchQuery = textAfterAt;
                            this.mentionStart = lastAtIndex;
                            this.showSuggestions = true;
                            this.selectedIndex = 0;
                            return;
                        }
                    }

                    this.showSuggestions = false;
                    this.searchQuery = '';
                },

                async fetchUsers(query) {
                    this.loading = true;
                    try {
                        const response = await fetch(`/api/users/search?query=${encodeURIComponent(query)}`);
                        this.suggestions = await response.json();
                    } catch (error) {
                        console.error('Error fetching users:', error);
                        this.suggestions = [];
                    }
                    this.loading = false;
                },

                selectUser(user) {
                    if (!this.trixEditor || !this.trixEditor.editor) return;

                    const editor = this.trixEditor.editor;
                    const position = editor.getPosition();

                    editor.setSelectedRange([this.mentionStart, position]);
                    editor.deleteInDirection('backward');

                    const mentionText = `@${user.name} `;
                    editor.insertString(mentionText);

                    this.showSuggestions = false;
                    this.searchQuery = '';
                    this.suggestions = [];
                },
            }));
        });
    </script>
</x-dynamic-component>
