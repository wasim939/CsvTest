<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <hot:HotelDetailsReq xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" TargetBranch="P7119574">
            <com:BillingPointOfSaleInfo xmlns:com="http://www.travelport.com/schema/common_v34_0" OriginApplication="UAPI"/>
            <hot:HotelProperty HotelChain="HI" HotelCode="43163" Name="HOLIDAY INN SYDNEY AIRPORT"/>
            <hot:HotelDetailsModifiers NumberOfAdults="1" RateRuleDetail="None">
                <com:PermittedProviders xmlns:com="http://www.travelport.com/schema/common_v34_0">
                    <com:Provider Code="1G"/>
                </com:PermittedProviders>
                <hot:HotelStay>
                    <hot:CheckinDate>2020-12-10</hot:CheckinDate>
                    <hot:CheckoutDate>2020-12-21</hot:CheckoutDate>
                </hot:HotelStay>
            </hot:HotelDetailsModifiers>
        </hot:HotelDetailsReq>
    </soapenv:Body>
</soapenv:Envelope>
