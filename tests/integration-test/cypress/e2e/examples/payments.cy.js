import Order from '../PageObjects/objects.cy'

describe('WooCommerce', function () {


    it('CC full capture and refund', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.refund()
                    } else {
                        cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })

    it('Klarna full capture and refund', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.refund()
                    } else {
                        cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })


    it('CC partial capture', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.partial_capture()
                    } else {
                        cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })

    it('CC partial refund', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.partial_refund()
                    } else {
                        cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })

    it('Klarna partial capture', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.partial_capture()
                    } else {
                        cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })

    it('Klarna partial refund', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.partial_refund()
                    } else {
                        cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })

    it('CC release payment', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.CC_TERMINAL_NAME + "')").length) {
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.release_payment()
                    } else {
                        cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })

    it('Klarna release payment', function () {

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.admin()
                ord.change_currency_to_DKK()
                ord.visit()
            }
        })
        ord.addproduct()
        cy.fixture('config').then((admin) => {
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                    if ($a.find("label:contains('" + admin.KLARNA_DKK_TERMINAL_NAME + "')").length) {
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.release_payment()
                    } else {
                        cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                        this.skip()
                    }

                })

            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                this.skip()
            }
        })
    })

    it('iDEAL Payment', function () {
        const ord = new Order()
        ord.visit()
        cy.wait(2000)
        cy.get('body').then(($body) => {
            if ($body.text().includes('€')) {
                ord.addproduct()
                cy.fixture('config').then((admin) => {
                    if (admin.iDEAL_EUR_TERMINAL != "") {
                        cy.get('body').then(($a) => {
                            if ($a.find("label:contains('" + admin.iDEAL_EUR_TERMINAL + "')").length) {
                                ord.ideal_payment(admin.iDEAL_EUR_TERMINAL)
                                ord.admin()
                                ord.ideal_refund()
                            } else {
                                cy.log(admin.iDEAL_EUR_TERMINAL + ' not found in page')
                                this.skip()
                            }

                        })

                    }
                    else {
                        cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                        this.skip()
                    }
                })

            }
            else {
                ord.admin()
                ord.change_currency_to_EUR_for_iDEAL()
                ord.visit()
                cy.wait(2000)
                ord.addproduct()
                cy.fixture('config').then((admin) => {
                    if (admin.iDEAL_EUR_TERMINAL != "") {
                        cy.get('body').then(($a) => {
                            if ($a.find("label:contains('" + admin.iDEAL_EUR_TERMINAL + "')").length) {
                                ord.ideal_payment(admin.iDEAL_EUR_TERMINAL)
                                ord.admin()
                                ord.ideal_refund()
                            } else {
                                cy.log(admin.iDEAL_EUR_TERMINAL + ' not found in page')
                                this.skip()
                            }

                        })

                    }
                    else {
                        cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
                        this.skip()
                    }
                })   
            }
        })
    })
})


