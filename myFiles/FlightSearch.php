<?php

/**
 * @Author: Umar Hayat
 * @Date:   2019-07-30 14:12:32
 * @Last Modified by:   mailm
 * @Last Modified time: 2020-01-09 13:29:52
 */
require_once "AirModel.php";
require_once "AirException.php";

class FlightSearch extends AirModel
{
	public static function minutesToReadable($input_minutes)
	{
		$days 		= (int) ($input_minutes / 1440);
		$rdays 		= $input_minutes - ($days * 1440); 
		$hours 		= (int) ($rdays / 60);
		$minutes 	= $rdays - ($hours * 60);

		return "$days:$hours:$minutes";
	}

	private static function listAirSegments($key, $lowFare)
	{	
		foreach($lowFare->children('air', true) as $airSegmentList)
		{		
			if((string) $airSegmentList->getName() == 'AirSegmentList')
			{
				foreach($airSegmentList->children('air', true) as $airSegment)
				{				
					if((string) $airSegment->getName() == 'AirSegment')
					{			
						foreach($airSegment->attributes() as $a => $b)
						{
							if((string) $a == 'Key')
							{							
								if((string) $b == $key)
								{								
									return $airSegment;
								}
							}
						}
					}
				}
			}
		}
	}

	private static function getFareInfo($key, $lowFare)
	{	
		foreach($lowFare->children('air', true) as $airFareInfoList)
		{		
			if((string) $airFareInfoList->getName() == 'FareInfoList')
			{			
				foreach($airFareInfoList->children('air', true) as $airFareInfo)
				{				
					if((string) $airFareInfo->getName() == 'FareInfo')
					{			
						foreach($airFareInfo->attributes() as $a => $b)
						{		
							if((string) $a == 'Key')
							{				
								if((string) $b == $key)
								{		
									return $airFareInfo;
								}
							}
						}
					}
				}
			}
		}
	}

	private static function getFlightDetails($key, $lowFare)
	{
		foreach($lowFare->children('air', true) as $airFlightDetailsList)
		{		
			if((string) $airFlightDetailsList->getName() == 'FlightDetailsList')
			{			
				foreach($airFlightDetailsList->children('air', true) as $airFlightDetails)
				{				
					if((string) $airFlightDetails->getName() == 'FlightDetails')
					{			
						foreach($airFlightDetails->attributes() as $a => $b)
						{				
							if((string) $a == 'Key')
							{				
								if((string) $b == $key)
								{						
									return $airFlightDetails;
								}
							}
						}
					}
				}
			}
		}
	}

	private static function listBookingInfo($key, $airPriceSol)
	{
		foreach($airPriceSol->children('air', true) as $node)
		{		
			if((string) $node->getName() == 'AirPricingInfo')
			{			
				foreach($node->children('air', true) as $sub_node)
				{				
					if((string) $sub_node->getName() == 'BookingInfo')
					{					
						foreach($sub_node->attributes() as $a => $b)
						{						
							if((string) $a == 'SegmentRef')
							{							
								if((string) $b == $key)
								{								
									return $sub_node;
								}
							}
						}
					}
				}
			}
		}
	}

	public static function parseResponse($isReturn = false)
	{
		if(!empty(self::$responseArray))
			return self::$responseArray;

		if(empty(self::$rawResponse))
		{
			throw new AirException("Response from server is empty.");
		}

		$xml 	= simplexml_load_String(self::$rawResponse, null, null, 'SOAP', true);	
		
		if(empty($xml))
		{
			throw new AirException("Encoding Error.");
			return false;
		}

		$Results = $xml->children('SOAP',true);
		foreach($Results->children('SOAP',true) as $fault)
		{
			if((string) $fault->getName() == 'Fault')
			{
				throw new AirException($fault->__toString());
				return false;
			}
		}
				
		$count = -1;
		foreach($Results->children('air', true) as $lowFare)
		{		
			foreach($lowFare->children('air',true) as $airPriceSol)
			{			
				if((string) $airPriceSol->getName() == 'AirPricingSolution')
				{
					$count 									= $count + 1;
					self::$responseArray[$count] 			= [];
					self::$responseArray[$count]['journey_ref_id'] 	= md5(time() . uniqid() . rand(99, 99999));
					self::$responseArray[$count]['connection']	= [];

					$journey_count 	= 0;
					$route_title 	= 'outbound_route';
					self::$responseArray[$count][$route_title] = [];
					self::$responseArray[$count][$route_title]['is_connecting_flight'] 	= false;

					foreach($airPriceSol->children('air', true) as $journey)
					{
						if((string) $journey->getName() == 'Journey')
						{
							$journey_count++;

							if($journey_count > 1 && $isReturn)
							{
								$route_title = 'inbound_route';
								if(!isset(self::$responseArray[$count][$route_title], self::$responseArray[$count][$route_title]['is_connecting_flight']))
								{
									self::$responseArray[$count][$route_title] = [];
									self::$responseArray[$count][$route_title]['is_connecting_flight'] 	= false;
								}
							}

							$readable_travel_time = '';
							$readable_travel_mins = '';
							foreach($journey->attributes() as $ja => $jb)
							{
								if((string) $ja == "TravelTime")
								{
									$very_temp = new DateInterval((string) $jb);
									$readable_travel_time = $very_temp->format('%D:%H:%I');
									$readable_travel_mins = ($very_temp->format('%D') * 1440) + ($very_temp->format('%H') * 60) + $very_temp->format('%I');
								}
							}

							$air_segment_count = 0;
							foreach($journey->children('air', true) as $segmentRef)
							{							
								if((string) $segmentRef->getName() == 'AirSegmentRef')
								{		
									$air_segment_count++;
									self::$responseArray[$count][$route_title][$air_segment_count - 1] 	= [];

									foreach($segmentRef->attributes() as $a => $b)
									{
										$booking_info = self::listBookingInfo($b, $airPriceSol);

										foreach($booking_info->attributes() as $aa => $bb)
										{
											if((string) $aa == "BookingCode")
											{
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['class'] = (string) $bb; 
											}
											elseif((string) $aa == "CabinClass")
											{
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['cabin_class'] = (string) $bb;
											}
											elseif((string) $aa == "FareInfoRef")
											{
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['fare_info_ref_key'] = (string) $bb;

												$fareInfo = self::getFareInfo($bb, $lowFare);
												foreach($fareInfo->attributes() as $cc => $dd)
												{
													if((string) $cc == "FareBasis")
													{
														self::$responseArray[$count][$route_title][$air_segment_count - 1]['fare_basis'] = (string) $dd;
													}
												}

												self::$responseArray[$count][$route_title][$air_segment_count - 1]['baggage_allowance'] = array();

												self::$responseArray[$count][$route_title][$air_segment_count - 1]['baggage_allowance']['weight'] = 0;
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['baggage_allowance']['unit'] = "Kilograms";
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['baggage_allowance']['pieces'] = 0;

												foreach($fareInfo->children('air', true) as $baggageAllowance)
												{
													if((string) $baggageAllowance->getName() == "BaggageAllowance")
													{
														foreach($baggageAllowance->children('air', true) as $baggageAllowanceChild)
														{
															if((string) $baggageAllowanceChild->getName() == "MaxWeight")
															{
																foreach($baggageAllowanceChild->attributes() as $cc => $dd)
																{
																	if((string) $cc == "Value")
																	{
																		self::$responseArray[$count][$route_title][$air_segment_count - 1]['baggage_allowance']['weight'] = (string) $dd;
																	}
																	elseif((string) $cc == "Unit")
																	{
																		self::$responseArray[$count][$route_title][$air_segment_count - 1]['baggage_allowance']['unit'] = (string) $dd;
																	}
																}
															}
															elseif((string) $baggageAllowanceChild->getName() == "NumberOfPieces")
															{
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['baggage_allowance']['pieces'] = (string) $baggageAllowanceChild;
															}
														}
													}
												}
											}
										}

										$segment = self::listAirSegments($b, $lowFare);

										self::$responseArray[$count][$route_title][$air_segment_count - 1]['air_segment_ref_key'] = (string) $b;

										foreach($segment->children('air', true) as $flightDetailsRef)
										{
											if((string) $flightDetailsRef->getName() == "FlightDetailsRef")
											{
												foreach($flightDetailsRef->attributes() as $fKey => $fVal)
												{
													if((string) $fKey == "Key")
													{
														self::$responseArray[$count][$route_title][$air_segment_count - 1]['flight_detail_ref_key'] = (string) $fVal;

														$flight_details = self::getFlightDetails($fVal, $lowFare);

														foreach($flight_details->attributes() as $fdKey => $fdVal)
														{
															if((string) $fdKey == "FlightTime")
															{
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['flight_time'] = (string) $fdVal;
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['flight_time_readable'] = self::minutesToReadable($fdVal);
															}
															elseif((string) $fdKey == "TravelTime")
															{
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['travel_time'] = $readable_travel_mins;//(string) $fdVal;
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['travel_time_readable'] =  $readable_travel_time;//self::minutesToReadable($fdVal);
															}
															elseif((string) $fdKey == "Equipment")
															{
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['plane'] 	=  (string) $fdVal;
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['equipment'] =  self::planeByCode($fdVal);
															}
															elseif((string) $fdKey == "OriginTerminal")
															{
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['origin_terminal'] = (string) $fdVal;
															}
															elseif((string) $fdKey == "DestinationTerminal")
															{
																self::$responseArray[$count][$route_title][$air_segment_count - 1]['origin_terminal'] = (string) $fdVal;
															}
														}
													}
												}
											}
										}

										foreach($segment->attributes() as $c => $d)
										{
											if((string) $c == "Origin")
											{
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['from']	= (string) $d;
											}
											elseif((string) $c == "Destination")
											{
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['to']	= (string) $d;
											}
											elseif((string) $c == "Carrier")
											{											
												$tempAirline 	= (string) $d;
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['carrier']	= $tempAirline;
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['airline']	= self::airlineByCode($tempAirline);
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['airline_logo']	= "https://bookme.pk/images/airlines/". $tempAirline .".svg";
											}
											elseif((string) $c == "FlightNumber")
											{
												$d = (string) $d;
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['flight']		= $tempAirline . $d;
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['flight_number']	= $d;
											}
											elseif((string) $c == "DepartureTime")
											{									
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['depart']	= (string) $d;			
											}
											elseif((string) $c == "ArrivalTime")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['arrival']	= (string) $d;	
											}
											elseif((string) $c == "Distance")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['distance']	= (string) $d;	
											}
											elseif((string) $c == "Group")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['group']	= (string) $d;	
											}
											elseif((string) $c == "ETicketability")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['e_ticket_ability']	= (string) $d;	
											}
											elseif((string) $c == "ChangeOfPlane")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['change_of_plane']	= (string) $d;	
											}
											elseif((string) $c == "ParticipantLevel")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['participant_level']	= (string) $d;	
											}
											elseif((string) $c == "LinkAvailability")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['link_availability']	= (string) $d;	
											}
											elseif((string) $c == "PolledAvailabilityOption")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['polled_availablility_option']	= (string) $d;	
											}
											elseif((string) $c == "OptionalServicesIndicator")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['optional_services_indicator']	= (string) $d;	
											}
											elseif((string) $c == "AvailabilitySource")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['availability_source']	= (string) $d;	
											}
											elseif((string) $c == "AvailabilityDisplayType")
											{											
												self::$responseArray[$count][$route_title][$air_segment_count - 1]['availability_display_type']	= (string) $d;	
											}
										}
									}
									if($air_segment_count > 1)
									{
										self::$responseArray[$count][$route_title]['is_connecting_flight'] = true;
									}
								}
							}										
						}
						elseif((string) $journey->getName() == 'Connection')
						{
							foreach($journey->attributes() as $ja => $jb)
							{
								if((string) $ja == 'SegmentIndex')
								{
									self::$responseArray[$count]['connection'][] = (int) $jb;
								}
							}
						}
						elseif((string) $journey->getName() == 'AirPricingInfo')
						{
							self::$responseArray[$count]['is_refundable'] = 'N/A';

							foreach($journey->attributes() as $g => $h)
							{
								if((string) $g == "Refundable")
								{
									self::$responseArray[$count]['is_refundable'] = $h == 'true' ? true : false;
								}
							}

							self::$responseArray[$count]['change_penalty'] = 'N/A';
							self::$responseArray[$count]['cancel_penalty'] = 'N/A';

							foreach($journey->children('air', true) as $childNode)
							{	
								if((string) $childNode->getName() == 'ChangePenalty')
								{
									foreach($childNode->children('air', true) as $changePenalty)
									{
										if((string) $changePenalty->getName() == 'Amount')
										{
											self::$responseArray[$count]['change_penalty'] = (string) $changePenalty;
										}
									}
								}
								elseif((string) $childNode->getName() == 'CancelPenalty')
								{
									foreach($childNode->children('air', true) as $cancelPenalty)
									{
										if((string) $cancelPenalty->getName() == 'Amount')
										{
											self::$responseArray[$count]['cancel_penalty'] = (string) $cancelPenalty;
										}
									}
								}
							}
						}
					}
					foreach($airPriceSol->attributes() as $e => $f)
					{
						if((string) $e == "ApproximateBasePrice")
						{
							self::$responseArray[$count]['approx_base_price']	= (string) $f;
						}
						elseif((string) $e == "ApproximateTaxes")
						{
							self::$responseArray[$count]['approx_taxes']	= (string) $f;
						}
						elseif((string) $e == "ApproximateTotalPrice")
						{											
							self::$responseArray[$count]['approx_total_price']	= (string) $f;
						}
						elseif((string) $e == "BasePrice")
						{
							self::$responseArray[$count]['base_price']	= (string) $f;
						}
						elseif((string) $e == "Taxes")
						{									
							self::$responseArray[$count]['taxes']	= (string) $f;			
						}
						elseif((string) $e == "TotalPrice")
						{											
							self::$responseArray[$count]['approx_total_price']	= (string) $f;	
						}
						elseif((string) $e == "Key")
						{
							self::$responseArray[$count]['air_pricing_solution_ref_key']	= (string) $f;
						}
					}


					if($journey_count > 1 && $isReturn)
					{
						self::$responseArray[$count]['direction'] = "return";
					}
					else
					{
						self::$responseArray[$count]['direction'] = "one_way";
					}
				}
			}
		}

		file_put_contents(getcwd() . '/cache/air/' . self::$cacheFile . '_parsed.dat', json_encode(self::$responseArray));

		return self::$responseArray;
	}
}