(()=>{"use strict";const e=window.React,t=window.wp.i18n,n=window.wc.wcBlocksRegistry,o=window.wp.htmlEntities,a=window.wc.wcSettings,c=window.wc.wcBlocksData,s=window.wp.data,i=(0,a.getSetting)("altapay_{terminal_id}_data",{}),{useEffect:r}=window.wp.element,l=(0,t.__)("AltaPay","woo-gutenberg-products-block"),p=(0,o.decodeEntities)(i.title)||l,w=e=>{const t=(0,s.select)(c.CART_STORE_KEY).getCartTotals();i.subtotal=t.total_price/100;const{eventRegistration:n,activePaymentMethod:a,emitResponse:l}=e,{onCheckoutSuccess:p}=n;return r((()=>{const e=p((e=>{var t=e.processingResponse.paymentDetails.order_id;return"yes"===i.is_apple_pay?(onApplePayButtonClicked(i,!1,null,t),{type:l.responseTypes.SUCCESS}):{type:l.responseTypes.SUCCESS}}));return()=>{e()}}),[l.responseTypes.SUCCESS,p]),jQuery(".wc-block-components-checkout-place-order-button").click((function(){jQuery("#radio-control-wc-payment-method-options-"+i.applepay_payment_method).is(":checked")&&onApplePayButtonClicked(i,!0,!1)})),(0,o.decodeEntities)(i.description||"")},d={name:"altapay_{terminal_id}",label:(0,e.createElement)(e.Fragment,null,(0,e.createElement)("span",{class:"altapay-payment-method"},(0,t.__)(p,"woocommerce-payments"),i.icon&&Array.isArray(i.icon)?i.icon.map(((t,n)=>(0,e.createElement)("img",{key:n,src:t,alt:`Icon ${n}`}))):i.icon&&(0,e.createElement)("img",{src:i.icon,alt:""}))),content:(0,e.createElement)(w,null),edit:(0,e.createElement)(w,null),canMakePayment:()=>!0,ariaLabel:p,supports:{features:i.supports}};(0,n.registerPaymentMethod)(d)})();