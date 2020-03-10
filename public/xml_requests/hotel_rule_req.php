<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0">
    <soapenv:Header/>
    <soapenv:Body>
        <hot:HotelRulesReq AuthorizedBy="user" TargetBranch="P3468561" TraceId="trace">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <hot:HotelRulesLookup Base="" RatePlanType="<?=$request->rate_plan_type?>">
                <hot:HotelProperty HotelChain="<?=$hotelInfo['hotelInfo']['HotelChain']?>" HotelCode="<?=$hotelInfo['hotelInfo']['HotelCode']?>" Name="<?=$hotelInfo['hotelInfo']['Name']?>"/>
                <hot:HotelStay>
                    <hot:CheckinDate>2020-12-10</hot:CheckinDate>
                    <hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
                </hot:HotelStay>
            </hot:HotelRulesLookup>
        </hot:HotelRulesReq>
    </soapenv:Body>
</soapenv:Envelope>
