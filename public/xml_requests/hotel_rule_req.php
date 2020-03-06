<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0">
    <soapenv:Header/>
    <soapenv:Body>
        <hot:HotelRulesReq AuthorizedBy="user" TargetBranch="P7119574" TraceId="trace">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <hot:HotelRulesLookup Base="" RatePlanType="<?=$request->rate_plan_type?>">
                <hot:HotelProperty HotelChain="<?=$hotelInfo['hotelInfo']['HotelChain']?>" HotelCode="<?=$hotelInfo['hotelInfo']['HotelCode']?>" Name="<?=$hotelInfo['hotelInfo']['Name']?>"/>
                <hot:HotelStay>
                    <hot:CheckinDate><?=$request->checkinDate?></hot:CheckinDate>
                    <hot:CheckoutDate><?=$request->checkoutDate?></hot:CheckoutDate>
                </hot:HotelStay>
            </hot:HotelRulesLookup>
        </hot:HotelRulesReq>
    </soapenv:Body>
</soapenv:Envelope>
