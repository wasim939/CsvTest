<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0">
    <soapenv:Header/>
    <soapenv:Body>
        <hot:HotelRulesReq AuthorizedBy="user" TargetBranch="P7119574" TraceId="trace">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <hot:HotelRulesLookup Base="" RatePlanType="N1QGOV">
                <hot:HotelProperty HotelChain="HI" HotelCode="43163" Name="HOLIDAY INN SYDNEY AIRPORT"/>
                <hot:HotelStay>
                    <hot:CheckinDate>2020-12-10</hot:CheckinDate>
                    <hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
                </hot:HotelStay>
            </hot:HotelRulesLookup>
        </hot:HotelRulesReq>
    </soapenv:Body>
</soapenv:Envelope>
