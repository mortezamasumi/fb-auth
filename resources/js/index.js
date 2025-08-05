window.otpResend = function (num, locale) {
    return {
        countDown: (num + 0) * 1000 + 0,
        countDownTimer: Date.now() + ((num + 0) * 1000 + 0),
        intervalID: null,
        locale,
        init() {
            if (!this.intervalID) {
                this.intervalID = setInterval(() => {
                    this.countDown = this.countDownTimer - new Date().getTime()
                }, 1000)
            }
        },
        getTime() {
            if (this.countDown < 0) {
                this.clearTimer()
            }
            return this.countDown
        },
        formatTime(num) {
            return this.pdigit(
                String(
                    Math.floor((num % (1000 * 60 * 60)) / (1000 * 60)),
                ).padStart(2, '0') +
                    ':' +
                    String(Math.floor((num % (1000 * 60)) / 1000)).padStart(
                        2,
                        '0',
                    ),
            )
        },
        clearTimer() {
            clearInterval(this.intervalID)
        },
        pdigit: function (number) {
            if (this.locale !== 'fa') {
                return number
            }

            return number.toString().replace(/\d/g, function (match) {
                return ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'][match]
            })
        },
    }
}

window.otpInput = function (length) {
    return {
        length,
        init() {
            this.$nextTick(() => {
                Array.from(Array(this.length)).forEach((element, i) => {
                    let ref = document.querySelector(
                        '[x-ref="input_' + i + '"]',
                    )
                    ref.value = this.state[i] || ''
                })
            })
        },
        handleInput(e) {
            this.state = Array.from(Array(this.length), (_, i) => {
                let ref = document.querySelector('[x-ref="input_' + i + '"]')
                return ref.value || ''
            }).join('')

            if (e.target.nextElementSibling && e.target.value) {
                e.target.nextElementSibling.focus()
            }
        },
        handlePaste(e) {
            this.state = e.clipboardData.getData('text')

            Array.from(Array(this.length)).forEach((element, i) => {
                let ref = document.querySelector('[x-ref="input_' + i + '"]')
                ref.value = this.state[i] || ''
            })
        },
        handleDelete(e) {
            let key = e.keyCode || e.charCode
            if (key == 8 || key == 46) {
                if (e.target !== this.$refs.input_0) {
                    e.target.previousElementSibling.focus()
                }
            }
        },
    }
}
