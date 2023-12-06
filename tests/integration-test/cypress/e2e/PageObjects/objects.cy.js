require('@cypress/xpath')

class Order {
    clrcookies() {
        cy.clearCookies()
    }
    visit() {
        cy.fixture('config').then((url) => {
            cy.visit(url.shopURL)

        })
    }


    addproduct() {
        cy.get('.nav-menu > li').contains('Shop').click()
        cy.xpath('/html/body/div/div[2]/div/div[2]/main/ul/li[2]/a[1]/img').click()
        cy.get('.single_add_to_cart_button').click()
        cy.get('.wc-block-components-notice-banner__content > .button').click().wait(3000)
        cy.contains('Proceed to Checkout').click().wait(3000)

    }

    addpartial_product() {
        cy.get('.nav-menu > li').contains('Shop').click()
        cy.xpath('/html/body/div/div[2]/div/div[2]/main/ul/li[1]/a[1]/img').click()
        cy.get('.single_add_to_cart_button').click()
    }

    cc_payment(CC_TERMINAL_NAME) {

        cy.contains(CC_TERMINAL_NAME).click({ force: true })
        //billing details
        cy.get('#billing_first_name').clear().type('Test')
        cy.get('#billing_last_name').clear().type('Person-dk')
        cy.get('#billing_address_1').clear().type('65 Nygårdsvej')
        cy.get('#billing_postcode').clear().type('2100')
        cy.get('#billing_city').clear().type('København Ø')
        cy.get('#billing_phone').clear().type('33 13 71 12')
        cy.get('#billing_email').clear().type('customer@email.dk')
        cy.get('#place_order').click()
        cy.get('[id=creditCardNumberInput]').type('4111111111111111')
        cy.get('#emonth').select('12')
        cy.get('#eyear').select('2025')
        cy.get('#cvcInput').type('123')
        cy.get('#cardholderNameInput').type('testname')
        cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(2000)
        cy.get('.entry-title').should('include.text', 'Order received')


    }

    klarna_payment(KLARNA_DKK_TERMINAL_NAME){
        cy.contains(KLARNA_DKK_TERMINAL_NAME).click({force: true}).wait(4000)
        cy.get('#billing_first_name').clear().type('Test')
        cy.get('#billing_last_name').clear().type('Person-dk')
        cy.get('#billing_address_1').clear().type('65 Nygårdsvej')
        cy.get('#billing_postcode').clear().type('2100')
        cy.get('#billing_city').clear().type('København Ø')
        cy.get('#billing_phone').clear().type('33 13 71 12')
        cy.get('#billing_email').clear().type('customer@email.dk')
        cy.get('#place_order').click().wait(10000)
        cy.get('#radio_pay_later').click().wait(3000)
        cy.get('#submitbutton').click({force:true}).wait(8000)
        cy.get('[id=klarna-pay-later-fullscreen]').wait(4000).then(function($iFrame){
            const mobileNum = $iFrame.contents().find('[id=email_or_phone]')
            cy.wrap(mobileNum).type('20222222')
            const continueBtn = $iFrame.contents().find('[id=onContinue]')
            cy.wrap(continueBtn).click().wait(2000)
        })
        cy.get('[id=klarna-pay-later-fullscreen]').wait(4000).then(function($iFrame){
            const otp = $iFrame.contents().find('[id=otp_field]')
            cy.wrap(otp).type('123456').wait(2000)
        })  
        cy.get('[id=klarna-pay-later-fullscreen]').wait(2000).then(function($iFrame){
            const contbtn = $iFrame.contents().find('[id=invoice_kp-purchase-review-continue-button]')
            cy.wrap(contbtn).click().wait(2000)
        })
    }

    admin() {
        cy.clearCookies()
        cy.fixture('config').then((admin) => {
            cy.visit(admin.adminURL)
            cy.get('#user_login').clear().wait(1000).type(admin.adminUsername)
            cy.get('#user_pass').clear().wait(1000).type(admin.adminPass)
            cy.get('#wp-submit').wait(1000).click()
            cy.get('body').then(($a) => {

                if ($a.find('.welcome-panel-content > h2').length) {
                    cy.get('.welcome-panel-content > h2').should('have.text', 'Welcome to WordPress!')
                }

            })

        })

    }

    capture() {

        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click().wait(3000)
        cy.get('body').then(($a) => {

            if ($a.find('.components-modal__header > .components-button').length) {
                cy.get('.components-modal__header > .components-button').click().wait(2000)
            }

        })

        cy.get("#toplevel_page_woocommerce > ul > li:nth-child(3) > a").click()
        cy.get('tr').eq(1).click()
        cy.get('#altapay_capture').click()
        cy.get('#altapay_capture').should('not.exist')

    }

    partial_capture() {

        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click().wait(3000)
        cy.get('body').then(($a) => {

            if ($a.find('.components-modal__header > .components-button').length) {
                cy.get('.components-modal__header > .components-button').click().wait(2000)

            }

        })

        cy.get("#toplevel_page_woocommerce > ul > li:nth-child(3) > a").click()
        cy.get('tr').eq(1).click()
        cy.get('.lh-copy > :nth-child(1) > :nth-child(7) > .form-control').click().clear().type('0').click()
        cy.get('#altapay_capture').click()
    }

    refund() {

        cy.get('[for="tab2"]').click()
        cy.get('#altapay_refund').click().wait(5000)
        cy.get('body').then(($a) => {
            if ($a.find(':nth-child(6) > tbody > :nth-child(1) > .label').length) {
                cy.get(':nth-child(6) > tbody > :nth-child(1) > .label').should('have.text', 'Refunded:')
            }
            else {
                cy.get(':nth-child(5) > tbody > :nth-child(1) > .label').should('have.text', 'Refunded:')
            }
        })
    }

    partial_refund() {

        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click({force:true}).wait(3000)
        cy.get('body').then(($a) => {

            if ($a.find('.components-modal__header > .components-button').length) {
                cy.get('.components-modal__header > .components-button').click().wait(2000)
            }
        })
        cy.get("#toplevel_page_woocommerce > ul > li:nth-child(3) > a").click()
        cy.get('tr').eq(1).click()
        cy.get('[for="tab2"]').click()
        cy.get('#refund > [style="overflow-x:auto;"] > .responsive-table > .w-100 > :nth-child(3) > :nth-child(1) > :nth-child(7) > .form-control').click({ force: true }).clear({ force: true }).type('0', { force: true }).click({ force: true })
        cy.get('#altapay_refund').click().wait(2000)


    }

    release_payment() {
        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click().wait(3000)
        cy.get('body').then(($a) => {

            if ($a.find('.components-modal__header > .components-button').length) {
                cy.get('.components-modal__header > .components-button').click().wait(2000)


            }

        })

        cy.get("#toplevel_page_woocommerce > ul > li:nth-child(3) > a").click()
        cy.get('tr').eq(1).click()
        cy.get('#altapay_release_payment').click()
    }

    change_currency_to_EUR_for_iDEAL() {
        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click()
        cy.get('a[href="admin.php?page=wc-settings"]').click()
        cy.get('#select2-woocommerce_currency-container').click()
        cy.get('.select2-dropdown > .select2-search > .select2-search__field').type('Euro (€){enter}')
        cy.get('.submit > .button-primary').click()
    }

    ideal_payment(iDEAL_EUR_TERMINAL) {
        cy.contains(iDEAL_EUR_TERMINAL).click({ force: true })
        cy.get('#billing_first_name').clear().type('Testperson-dk')
        cy.get('#billing_last_name').clear().type('Approved')
        cy.get('#billing_address_1').clear().type('Sæffleberggate 56,1 mf')
        cy.get('#billing_postcode').clear().type('6800')
        cy.get('#billing_city').clear().type('Varde')
        cy.get('#billing_phone').clear().type('20123456')
        cy.get('#billing_email').clear().type('demo@example.com')
        cy.get('#place_order').click()
        cy.get('#idealIssuer').select('AltaPay test issuer 1')
        cy.get('#pensioPaymentIdealSubmitButton').click()
        cy.get('[type="text"]').type('shahbaz.anjum123-facilitator@gmail.com')
        cy.get('[type="password"]').type('Altapay@12345')
        cy.get('#SignInButton').click()
        cy.get(':nth-child(3) > #successSubmit').click().wait(1000)

    }

    ideal_refund() {
        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click().wait(3000)
        cy.get('body').then(($a) => {

            if ($a.find('.components-modal__header > .components-button').length) {
                cy.get('.components-modal__header > .components-button').click().wait(2000)


            }

        })

        cy.get("#toplevel_page_woocommerce > ul > li:nth-child(3) > a").click()
        cy.get('tr').eq(1).click()
        cy.get('[for="tab2"]').click()
        cy.get('#altapay_refund').click().wait(5000)
        cy.get('[for="tab2"]').click()
        cy.get('body').then(($a) => {
            if ($a.find(':nth-child(6) > tbody > :nth-child(1) > .label').length) {
                cy.get(':nth-child(6) > tbody > :nth-child(1) > .label').should('have.text', 'Refunded:')
            }
            else {
                cy.get(':nth-child(5) > tbody > :nth-child(1) > .label').should('have.text', 'Refunded:')
            }
        })
    }

    change_currency_to_DKK() {
        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click()
        cy.get('a[href="admin.php?page=wc-settings"]').click()
        cy.get('#select2-woocommerce_currency-container').click()
        cy.get('.select2-dropdown > .select2-search > .select2-search__field').type('Danish Krone{enter}')
        cy.get('.submit > .button-primary').click()
    }

    create_fixed_discount() {
        cy.get('#toplevel_page_woocommerce-marketing > .wp-has-submenu > .wp-menu-name').click()
        cy.get('#toplevel_page_woocommerce-marketing > ul > li:nth-child(3) > a').click()
        cy.get('.page-title-action').click()
        cy.get('#title').type('fixed')
        cy.get('#discount_type').select('Fixed cart discount')
        cy.get('#coupon_amount').clear().type('10.5')
        cy.get('#publish').click()
    }

    create_percentage_discount() {
        cy.get('#toplevel_page_woocommerce-marketing > .wp-has-submenu > .wp-menu-name').click()
        cy.get('#toplevel_page_woocommerce-marketing > ul > li:nth-child(3) > a').click()
        cy.get('.page-title-action').click()
        cy.get('#title').type('percentage')
        cy.get('#discount_type').select('Percentage discount')
        cy.get('#coupon_amount').clear().type('10.5')
        cy.get('#publish').click()
    }

    apply_fixed_discount() {
        cy.get('.nav-menu > li').contains('Shop').click()
        cy.xpath('/html/body/div/div[2]/div/div[2]/main/ul/li[2]/a[1]/img').click()
        cy.get('.single_add_to_cart_button').click()
        cy.get('.woocommerce-message > .button').click()
        cy.get('#coupon_code').type('fixed')
        cy.get('.coupon > .button').click()
        cy.get('.checkout-button').click()
    }

    apply_percentage_discount() {
        cy.get('.nav-menu > li').contains('Shop').click()
        cy.xpath('/html/body/div/div[2]/div/div[2]/main/ul/li[2]/a[1]/img').click()
        cy.get('.single_add_to_cart_button').click()
        cy.get('.woocommerce-message > .button').click()
        cy.get('#coupon_code').type('percentage')
        cy.get('.coupon > .button').click()
        cy.get('.checkout-button').click()
    }

    apply_fixed_discount() {
        cy.get('.nav-menu > li').contains('Shop').click()
        cy.xpath('/html/body/div/div[2]/div/div[2]/main/ul/li[2]/a[1]/img').click()
        cy.get('.single_add_to_cart_button').click()
        cy.get('.woocommerce-message > .button').click()
        cy.get('#coupon_code').type('fixed')
        cy.get('.coupon > .button').click()
        cy.get('.checkout-button').click()
    }

}

export default Order
