<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <hot:HotelDetailsReq xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" ReturnMediaLinks="true" TargetBranch="P7119574">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <hot:HotelProperty HotelChain="<?=$hotelInfo['hotelInfo']['HotelChain']?>" HotelCode="<?=$hotelInfo['hotelInfo']['HotelCode']?>" Name="<?=$hotelInfo['hotelInfo']['Name']?>"/>
            <hot:HotelDetailsModifiers NumberOfAdults="1" RateRuleDetail="Complete">
                <com:PermittedProviders>
                    <com:Provider Code="1G"/>
                </com:PermittedProviders>
                <hot:HotelStay>
                    <hot:CheckinDate>2020-12-10</hot:CheckinDate>
                    <hot:CheckoutDate>2020-12-20</hot:CheckoutDate>
                </hot:HotelStay>
            </hot:HotelDetailsModifiers>
        </hot:HotelDetailsReq>
    </soapenv:Body>
</soapenv:Envelope>
