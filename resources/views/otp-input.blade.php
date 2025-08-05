<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
        ...otpInput({{ config('fb-auth.otp_digits') }}),
    }">
        <div data-field-wrapper="" class="otp-wrapper fi-fo-field-wrp">
            <div class="otp-cells-group" dir="ltr">
                <template x-for="(_, index) in length" :key="`input_${index}`">
                    <input type="tel"
                        class="otp-cell dark:text-slate-200 dark:bg-slate-700 dark:hover:border-slate-500 text-slate-900 bg-slate-300 hover:bg-slate-400 focus:bg-slate-200 focus:border-indigo-400  focus:ring-indigo-100 dark:focus:bg-slate-500 dark:focus:border-indigo-800 dark:focus:ring-indigo-700"
                        pattern="\d*" maxlength="1" x-on:input="handleInput($event)" x-on:paste="handlePaste($event)"
                        x-on:keyup="handleDelete($event)" :x-ref="`input_${index}`" />
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>
