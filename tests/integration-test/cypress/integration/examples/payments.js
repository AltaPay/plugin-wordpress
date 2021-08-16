import Order from '../PageObjects/objects'

describe ('WooCommerce', function(){


    it('CC full capture and refund', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.CC_TERMINAL_NAME+"')").length){
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.refund()
                }else{
                    cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
            } 
        })
    })

    it('Klarna full capture and refund', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.KLARNA_DKK_TERMINAL_NAME+"')").length){
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.refund()
                }else{
                    cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
            } 
        })
    })


    it('CC partial capture', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.CC_TERMINAL_NAME+"')").length){
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.partial_capture()
                }else{
                    cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
            } 
        })
    })

    it('CC partial refund', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.CC_TERMINAL_NAME+"')").length){
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.partial_refund()
                }else{
                    cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
            } 
        })
    })
    
    it('Klarna partial capture', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.KLARNA_DKK_TERMINAL_NAME+"')").length){
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.partial_capture()
                }else{
                    cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
            } 
        })
    })

    it('Klarna partial refund', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addpartial_product()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.KLARNA_DKK_TERMINAL_NAME+"')").length){
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.capture()
                        ord.partial_refund()
                }else{
                    cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
            } 
        })
    })

    it('CC release payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.CC_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.CC_TERMINAL_NAME+"')").length){
                        ord.cc_payment(admin.CC_TERMINAL_NAME)
                        ord.admin()
                        ord.release_payment()
                }else{
                    cy.log(admin.CC_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('CC_TERMINAL_NAME skipped')
            } 
        })
    })

    it('Klarna release payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        cy.fixture('config').then((admin)=>{
            if (admin.KLARNA_DKK_TERMINAL_NAME != "") {
                cy.get('body').then(($a) => {
                if($a.find("label:contains('"+admin.KLARNA_DKK_TERMINAL_NAME+"')").length){
                        ord.klarna_payment(admin.KLARNA_DKK_TERMINAL_NAME)
                        ord.admin()
                        ord.release_payment()
                }else{
                    cy.log(admin.KLARNA_DKK_TERMINAL_NAME + ' not found in page')
                }

                })
            
            }
            else {
                cy.log('KLARNA_DKK_TERMINAL_NAME skipped')
            } 
        })
    })

})