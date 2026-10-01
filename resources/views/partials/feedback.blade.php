{{--
    In-game feedback (R15, closed beta). A fixed button opens a sol-modal; the
    form posts category + message + current path to /feedback. User, run and
    Sol are attached server-side (FeedbackController::store()).
--}}
<div x-data="feedbackDialog()" data-feedback-dialog>
    <button type="button"
        class="feedback-trigger {{ Auth::user()->role === "admin" ? "feedback-trigger--above-debug" : "" }}"
        title="{{ __("feedback.button_title") }}" @click="open()">
        <i class="bi bi-chat-left-text" aria-hidden="true"></i>
        <span>{{ __("feedback.button") }}</span>
    </button>

    <dialog x-ref="dialog" class="sol-modal feedback-dialog" @close="reset()">
        <article>
            <header>
                <h3>{{ __("feedback.title") }}</h3>
                <button type="button" class="sol-modal-close" @click="$refs.dialog.close()"
                    aria-label="{{ __("feedback.cancel") }}">&#x2715;</button>
            </header>

            <section x-show="!sent">
                <p class="feedback-dialog__intro">{{ __("feedback.intro") }}</p>

                <label for="feedback-category">{{ __("feedback.category") }}
                    <select id="feedback-category" x-model="category">
                        <option value="bug">{{ __("feedback.category_bug") }}</option>
                        <option value="balance">{{ __("feedback.category_balance") }}</option>
                        <option value="idea">{{ __("feedback.category_idea") }}</option>
                        <option value="other">{{ __("feedback.category_other") }}</option>
                    </select>
                </label>

                <label for="feedback-message">{{ __("feedback.message") }}
                    <textarea id="feedback-message" x-model="message" rows="5" maxlength="2000"
                        placeholder="{{ __("feedback.message_placeholder") }}"></textarea>
                </label>

                <p class="feedback-dialog__error" x-show="error" x-text="error" role="alert"></p>
            </section>

            <section x-show="sent">
                <p role="status">{{ __("feedback.thanks") }}</p>
            </section>

            <footer>
                <button type="button" class="secondary" @click="$refs.dialog.close()"
                    x-text="sent ? @js(__("feedback.close")) : @js(__("feedback.cancel"))"></button>
                <button type="button" x-show="!sent" @click="submit()"
                    :disabled="sending || message.trim() === ''"
                    x-text="sending ? @js(__("feedback.sending")) : @js(__("feedback.send"))"></button>
            </footer>
        </article>
    </dialog>
</div>

<script>
    function feedbackDialog() {
        return {
            category: 'bug',
            message: '',
            sending: false,
            sent: false,
            error: '',

            open() {
                this.$refs.dialog.showModal();
            },

            reset() {
                if (this.sent) {
                    this.message = '';
                    this.category = 'bug';
                }
                this.sent = false;
                this.sending = false;
                this.error = '';
            },

            async submit() {
                if (this.sending || this.message.trim() === '') return;
                this.sending = true;
                this.error = '';

                try {
                    const resp = await fetch(@js(route("feedback.store")), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            category: this.category,
                            message: this.message,
                            page: window.location.pathname,
                        }),
                    });

                    if (resp.status === 429) {
                        this.error = @js(__("feedback.rate_limited"));
                    } else if (!resp.ok) {
                        this.error = @js(__("feedback.error"));
                    } else {
                        this.sent = true;
                    }
                } catch (e) {
                    this.error = @js(__("feedback.error"));
                } finally {
                    this.sending = false;
                }
            },
        };
    }
</script>
