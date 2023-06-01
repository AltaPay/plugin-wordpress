import Order from '../PageObjects/objects.cy'

if (Cypress.env('runDiscountsTests')) {

    describe('WooCommerce', function () {
        it('Create fixed discount', function () {
            const ord = new Order()
            ord.clrcookies()
            ord.admin()
            ord.create_fixed_discount()
        })
        it('Create percentage discount', function () {
            const ord = new Order()
            ord.clrcookies()
            ord.admin()
            ord.create_percentage_discount()
        })

        it('Apply fixed discount with CC', function () {
            const ord = new Order()
            ord.visit()
            cy.get('body').then(($body) => {
                if ($body.text().includes('€')) {
                    ord.admin()
                    ord.change_currency_to_DKK()
                    ord.visit()
                }
            })
            ord.apply_fixed_discount()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').wait(3000).then(($a) => {
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

        it('Apply percentage discount with CC', function () {
            const ord = new Order()
            ord.visit()
            cy.get('body').then(($body) => {
                if ($body.text().includes('€')) {
                    ord.admin()
                    ord.change_currency_to_DKK()
                    ord.visit()
                }
            })
            ord.apply_percentage_discount()
            cy.fixture('config').then((admin) => {
                if (admin.CC_TERMINAL_NAME != "") {
                    cy.get('body').wait(3000).then(($a) => {
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

        it('Apply percentage discount with Klarna', function () {
            const ord = new Order()
            ord.visit()
            cy.get('body').then(($body) => {
                if ($body.text().includes('€')) {
                    ord.admin()
                    ord.change_currency_to_DKK()
                    ord.visit()
                }
            })
            ord.apply_percentage_discount()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').wait(3000).then(($a) => {
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

        it('Apply fixed discount with Klarna', function () {
            const ord = new Order()
            ord.visit()
            cy.log('Klarna Fixed Discount Not Supported Yet')
            this.skip()
            cy.get('body').then(($body) => {
                if ($body.text().includes('€')) {
                    ord.admin()
                    ord.change_currency_to_DKK()
                    ord.visit()
                }
            })
            ord.apply_fixed_discount()
            cy.fixture('config').then((admin) => {
                if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                    cy.get('body').wait(3000).then(($a) => {
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

        it('Apply fixed discount with iDEAL', function () {
            const ord = new Order()
            ord.visit()
            cy.get('body').then(($body) => {
                if ($body.text().includes('DKK')) {
                    ord.admin()
                    ord.change_currency_to_EUR_for_iDEAL()
                    ord.visit()
                }
            })
            ord.apply_fixed_discount()
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
                    cy.log('iDEAL_EUR_TERMINAL skipped')
                    this.skip()
                }
            })
        })

        it('Apply percentage discount with iDEAL', function () {
            const ord = new Order()
            ord.visit()
            ord.apply_percentage_discount()
            cy.fixture('config').then((admin) => {
                if (admin.iDEAL_EUR_TERMINAL != "") {
                    cy.get('body').then(($a) => {
                        if ($a.find("label:contains('" + admin.iDEAL_EUR_TERMINAL + "')").length) {
                            ord.ideal_payment(admin.iDEAL_EUR_TERMINAL)
                            ord.admin()
                            ord.ideal_refund()
                        } else {
                            cy.log(admin.iDEAl_EUR_TERMINAL + ' not found in page')
                            this.skip()
                        }

                    })

                }
                else {
                    cy.log('iDEAL_EUR_TERMINAL skipped')
                    this.skip()
                }
            })
        })

    })
}