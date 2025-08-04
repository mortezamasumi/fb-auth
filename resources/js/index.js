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
