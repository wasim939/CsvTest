<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <hot:HotelDetailsReq xmlns:com="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" ReturnMediaLinks="true" TargetBranch="P3468561">
            <com:BillingPointOfSaleInfo OriginApplication="UAPI"/>
            <hot:HotelProperty HotelChain="<?=$hotelInfo['hotelInfo']['HotelChain']?>" HotelCode="<?=$hotelInfo['hotelInfo']['HotelCode']?>" Name="<?=$hotelInfo['hotelInfo']['Name']?>"/>
            <hot:HotelDetailsModifiers NumberOfAdults="<?=$request->no_of_adults?>" RateRuleDetail="Complete">
                <com:PermittedProviders>
                    <com:Provider Code="1G"/>
                </com:PermittedProviders>
                <hot:HotelStay>
                    <hot:CheckinDate><?=$request->checkinDate?></hot:CheckinDate>
                    <hot:CheckoutDate><?=$request->checkoutDate?></hot:CheckoutDate>
                </hot:HotelStay>
            </hot:HotelDetailsModifiers>
        </hot:HotelDetailsReq>
    </soapenv:Body>
</soapenv:Envelope>
