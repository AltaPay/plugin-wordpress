require('cypress-xpath')

class Order
{
    clrcookies(){
        cy.clearCookies()
    }
    visit()
    {
        cy.fixture('config').then((url)=>{
        cy.visit(url.url) 
        cy.get('.nav-menu > .page-item-9 > a').click()   
            })    
    }

    signin(){
        cy.fixture('config').then((signin)=>{
        cy.get('#username').type(signin.usrname)
        cy.get('#password').type(signin.pass)
        cy.get('.woocommerce-button').click()
        cy.wait(1000)
        cy.get('.beta > a').click().wait(1000)
        cy.wait(1000)
        })
    }
    
    addproduct(){
        cy.get('.wp-block-product-new > .wc-block-grid__products > :nth-child(5) > .wc-block-grid__product-link > .wc-block-grid__product-image > .attachment-woocommerce_thumbnail').click()
        cy.xpath('/html/body/div[2]/div[2]/div/div[2]/main/div[2]/div[2]/form/div/input').click().clear().type('3')
        cy.get('.single_add_to_cart_button').click()
        cy.get('.woocommerce-message > .button').click()
        cy.get('.checkout-button').click()
        
    }

    cc_payment(){
        cy.get('.wc_payment_method.payment_method_altapay_embraceit_test_terminal > label').click({force: true})
        cy.xpath('/html/body/div[2]/div[2]/div/div[1]/main/article/div[1]/div/form[2]/div[2]/div/div/button').click({force: true})
        cy.get('[id=creditCardNumberInput]').type('4111111111111111')
        cy.get('#emonth').type('01')
        cy.get('#eyear').type('2023')
        cy.get('#cvcInput').type('123')
        cy.get('#cardholderNameInput').type('testname')
        cy.get('#pensioCreditCardPaymentSubmitButton').click().wait(2000)
        cy.get('.entry-title').should('include.text', 'Order received')

    
    }
    save_oid(){
        let txt 
        cy.get('.product-quantity').click().then(($oid) => {

    // store Order id
        txt = $oid.text()
        cy.log(txt)
    })
    }
    klarna_payment(){

        cy.get('.payment_method_altapay_embraceit_klarna_dkk_test_terminal > label').click({force: true}).wait(1000)
        cy.get('#place_order').click().wait(3000)
        cy.wait(3000)
        cy.get('#submitbutton').click().wait(10000)

    

        cy.get('[id=klarna-pay-later-fullscreen]').wait(2000).then(function($iFrame){
            const mobileNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-phone-number]')
            cy.wrap(mobileNum).type('(452) 012-3456')
            const personalNum = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-national-identification-number]')
            cy.wrap(personalNum).type('1012201234')
            const submit = $iFrame.contents().find('[id=invoice_kp-purchase-approval-form-continue-button]')
            cy.wrap(submit).click()
            
        })
        
        cy.wait(3000)
         cy.get('.entry-title').should('have.text', 'Order received')
        
        
    }

    admin()
    {
            cy.clearCookies()
            cy.fixture('config').then((admin)=>{
            cy.visit(admin.url_admin_url)
            cy.get('#user_login').clear().type(admin.wp_admin_user)
            cy.get('#user_pass').type(admin.wp_admin_pass)
            cy.get('#wp-submit').wait(1000).click()
            cy.get('.welcome-panel-content > h2').should('have.text', 'Welcome to WordPress!')
            })

    }

    capture(){

        cy.get('#toplevel_page_woocommerce > .wp-has-submenu > .wp-menu-name').click()
        cy.get("#toplevel_page_woocommerce > ul > li:nth-child(3) > a").click()
        cy.get('tr').eq(1).should('contain', 'Processing').click()
        cy.get('#altapay_capture').click()
        cy.get('#altapay_capture').should('not.exist')
        

        
    }

    refund(){

        cy.get('[for="tab2"]').click()
        cy.get('#altapay_refund').click()
        cy.get(':nth-child(5) > tbody > :nth-child(1) > .label').should('have.text', 'Refunded:')

    }


    

}

export default Order