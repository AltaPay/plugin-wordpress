import Order from '../PageObjects/objects'

describe ('WooCommerce', function(){

    it('CC Payment', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        ord.cc_payment()
        ord.save_oid()
        ord.admin()
        ord.capture()
        ord.refund()
    })


    it('klarna', function(){

        const ord = new Order()
        ord.clrcookies()
        ord.visit()
        ord.addproduct()
        ord.klarna_payment()
        ord.admin()
        ord.capture()
        ord.refund()
    })

})