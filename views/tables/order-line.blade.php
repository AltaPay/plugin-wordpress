<tr class="ap-orderlines-{{$type}}">
    <td style="display:none">
        <input class="form-control pv3 pr3 bb b--black-20" name="productID" type="text" value="{{$itemId}}"/>
    </td>
    <td class="pv3 pr3 bb b--black-20">{{$itemName}}</td>
    <td class="pv3 pr3 bb b--black-20">{{$priceWithTax}}</td>
    <td class="pv3 pr3 bb b--black-20">{{$priceWithoutTax}}</td>
    <td class="ap-orderline-{{$type}}-max-quantity pv3 pr3 bb b--black-20">{{ $qty }}</td>
    <td class="pv3 pr3 bb b--black-20">{{$discountPercent}}</td>
    <td class="pv3 pr3 bb b--black-20">
        <input class="form-control ap-order-{{$type}}-modify" name="qty"
               value="{{$availableQty}}" type="number" style="width: 100px;" {{ !$availableQty ? 'disabled' : '' }} />
    </td>
    <td class="pv3 pr3 bb b--black-20">
        <span class="totalprice-{{$type}}">{{$currency}} {{$totalIncTax}}</span>
    </td>
</tr>