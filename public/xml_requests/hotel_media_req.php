<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <hot:HotelMediaLinksReq xmlns:common_v34_0="http://www.travelport.com/schema/common_v34_0" xmlns:hot="http://www.travelport.com/schema/hotel_v34_0" AuthorizedBy="user" Gallery="true" RichMedia="true" TargetBranch="P7119574" TraceId="trace">
            <com:BillingPointOfSaleInfo xmlns:com="http://www.travelport.com/schema/common_v34_0" OriginApplication="UAPI"/>
            <hot:HotelProperty HotelChain="<?=$hotelInfo['hotelInfo']['HotelChain']?>" HotelCode="<?=$hotelInfo['hotelInfo']['HotelCode']?>" HotelLocation="<?=$hotelInfo['hotelInfo']['HotelLocation']?>" HotelTransportation="<?=$hotelInfo['hotelInfo']['HotelTransportation']?>" Name="<?=$hotelInfo['hotelInfo']['Name']?>" ParticipationLevel="<?=$hotelInfo['hotelInfo']['ParticipationLevel']?>" ReserveRequirement="<?=$hotelInfo['hotelInfo']['ReserveRequirement']?>" VendorLocationKey="<?=$hotelInfo['hotelInfo']['VendorLocationKey']?>">
                <hot:PropertyAddress>
                    <hot:Address><?=$hotelInfo['addressInfo']['address']['streetInfo']?></hot:Address>
                </hot:PropertyAddress>
            </hot:HotelProperty>
        </hot:HotelMediaLinksReq>
    </soapenv:Body>
</soapenv:Envelope>
