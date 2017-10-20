<?php
  function invokeGetReport(MarketplaceWebService_Interface $service, $request)
  {
      try {
              $response = $service->getReport($request);

//                echo ("Service Response\r\n<br />");
//                echo ("=============================================================================\r\n<br />");

//                echo("        GetReportResponse\r\n<br />");
                if ($response->isSetGetReportResult()) {
                  $getReportResult = $response->getGetReportResult();
//                  echo ("            GetReport");

                  if ($getReportResult->isSetContentMd5()) {
//                    echo ("                ContentMd5");
                    $return_echo["ContentMd5"] = $getReportResult->getContentMd5();
                  }
                }
                if ($response->isSetResponseMetadata()) {
//                    echo("            ResponseMetadata\r\n<br />");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId())
                    {
//                        echo("                RequestId\r\n<br />");
                        $return_echo["RequestId"] = $responseMetadata->getRequestId();
                    }
                }

//                echo ("        Report Contents\r\n<br />");
//                echo (stream_get_contents($request->getReport()) . "\r\n<br />");
                $return_echo["Report_Contents"] = stream_get_contents($request->getReport());

                $return_echo["ResponseHeaderMetadata"] = $response->getResponseHeaderMetadata();

	return $return_echo;
     } catch (MarketplaceWebService_Exception $ex) {
         $return_echo["Caught_Exception"] = $ex->getMessage();
         $return_echo["Response_Status_Code"] = $ex->getStatusCode();
         $return_echo["Error_Code"] = $ex->getErrorCode();
         $return_echo["Error_Type"] = $ex->getErrorType();
         $return_echo["Request_ID"] = $ex->getRequestId();
         $return_echo["XML"] = $ex->getXML();
         $return_echo["ResponseHeaderMetadata"] = $ex->getResponseHeaderMetadata();

	return $return_echo;
     }
 }

  function invokeRequestReport(MarketplaceWebService_Interface $service, $request)
  {
      try {
              $response = $service->requestReport($request);

//                echo ("Service Response\r\n<br />");
//                echo ("=============================================================================\r\n<br />");

//                echo("        RequestReportResponse\r\n<br />");
                if ($response->isSetRequestReportResult()) {
//                    echo("            RequestReportResult\r\n<br />");
                    $requestReportResult = $response->getRequestReportResult();

                    if ($requestReportResult->isSetReportRequestInfo()) {

                        $reportRequestInfo = $requestReportResult->getReportRequestInfo();
//                          echo("                ReportRequestInfo\r\n<br />");
                          if ($reportRequestInfo->isSetReportRequestId())
                          {
//                              echo("                    ReportRequestId\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getReportRequestId() . "\r\n<br />");
                              $return_echo["ReportRequestId"] = $reportRequestInfo->getReportRequestId();
                          }
                          if ($reportRequestInfo->isSetReportType())
                          {
//                              echo("                    ReportType\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getReportType() . "\r\n<br />");
                              $return_echo["ReportType"] = $reportRequestInfo->getReportType();
                          }
                          if ($reportRequestInfo->isSetStartDate())
                          {
//                              echo("                    StartDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getStartDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["StartDate"] = $reportRequestInfo->getStartDate()->format(DATE_FORMAT);
                          }
                          if ($reportRequestInfo->isSetEndDate())
                          {
//                              echo("                    EndDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getEndDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["EndDate"] = $reportRequestInfo->getEndDate()->format(DATE_FORMAT);
                          }
                          if ($reportRequestInfo->isSetSubmittedDate())
                          {
//                              echo("                    SubmittedDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["SubmittedDate"] = $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT);
                          }
                          if ($reportRequestInfo->isSetReportProcessingStatus())
                          {
//                              echo("                    ReportProcessingStatus\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getReportProcessingStatus() . "\r\n<br />");
                              $return_echo["ReportProcessingStatus"] = $reportRequestInfo->getReportProcessingStatus();
                          }
                      }
                }
                if ($response->isSetResponseMetadata()) {
//                    echo("            ResponseMetadata\r\n<br />");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId())
                    {
//                        echo("                RequestId\r\n<br />");
//                        echo("                    " . $responseMetadata->getRequestId() . "\r\n<br />");
                        $return_echo["RequestId"] = $responseMetadata->getRequestId();
                    }
                }

//                echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\r\n<br />");
                $return_echo["ResponseHeaderMetadata"] = $response->getResponseHeaderMetadata();

		return $return_echo;

     } catch (MarketplaceWebService_Exception $ex) {

/*
         echo("Caught Exception: " . $ex->getMessage() . "\r\n<br />");
         echo("Response Status Code: " . $ex->getStatusCode() . "\r\n<br />");
         echo("Error Code: " . $ex->getErrorCode() . "\r\n<br />");
         echo("Error Type: " . $ex->getErrorType() . "\r\n<br />");
         echo("Request ID: " . $ex->getRequestId() . "\r\n<br />");
         echo("XML: " . $ex->getXML() . "\r\n<br />");
         echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\r\n<br />");
*/

        $return_echo["function"] = "invokeRequestReport";
        $return_echo["Caught_Exception"] = $ex->getMessage();
        $return_echo["Response_Status_Code"] = $ex->getStatusCode();
        $return_echo["Error_Code"] = $ex->getErrorCode();
        $return_echo["Error_Type"] = $ex->getErrorType();
        $return_echo["Request_ID"] = $ex->getRequestId();
        $return_echo["XML"] = $ex->getXML();
        $return_echo["ResponseHeaderMetadata"] = $ex->getResponseHeaderMetadata();
        $return_echo["message"] = "Delay 2 minutes and trying the same Request";
        func_print_r($return_echo);
        return $return_echo;

     }
 }

  function invokeGetReportRequestList(MarketplaceWebService_Interface $service, $request)
  {
      try {
              $response = $service->getReportRequestList($request);

//                echo ("Service Response\r\n<br />");
//                echo ("=============================================================================\r\n<br />");

//                echo("        GetReportRequestListResponse\r\n<br />");
                if ($response->isSetGetReportRequestListResult()) {
//                    echo("            GetReportRequestListResult\r\n<br />");
                    $getReportRequestListResult = $response->getGetReportRequestListResult();
                    if ($getReportRequestListResult->isSetNextToken())
                    {
//                        echo("                NextToken\r\n<br />");
//                        echo("                    " . $getReportRequestListResult->getNextToken() . "\r\n<br />");
                        $return_echo["NextToken"] = $getReportRequestListResult->getNextToken();
                    }
                    if ($getReportRequestListResult->isSetHasNext())
                    {
//                        echo("                HasNext\r\n<br />");
//                        echo("                    " . $getReportRequestListResult->getHasNext() . "\r\n<br />");
                        $return_echo["HasNext"] = $getReportRequestListResult->getHasNext();
                    }
                    $reportRequestInfoList = $getReportRequestListResult->getReportRequestInfoList();
                    foreach ($reportRequestInfoList as $reportRequestInfo) {
//                        echo("                ReportRequestInfo\r\n<br />");
                    if ($reportRequestInfo->isSetReportRequestId())
                          {
//                              echo("                    ReportRequestId\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getReportRequestId() . "\r\n<br />");
                              $return_echo["ReportRequestId"] = $reportRequestInfo->getReportRequestId();
                          }
                          if ($reportRequestInfo->isSetReportType())
                          {
//                              echo("                    ReportType\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getReportType() . "\r\n<br />");
                              $return_echo["ReportType"] = $reportRequestInfo->getReportType();
                          }
                          if ($reportRequestInfo->isSetStartDate())
                          {
//                              echo("                    StartDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getStartDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["StartDate"] = $reportRequestInfo->getStartDate()->format(DATE_FORMAT);
                          }
                          if ($reportRequestInfo->isSetEndDate())
                          {
//                              echo("                    EndDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getEndDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["EndDate"] = $reportRequestInfo->getEndDate()->format(DATE_FORMAT);
                          }
                          // add start
                          if ($reportRequestInfo->isSetScheduled())
                          {
//                              echo("                    Scheduled\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getScheduled() . "\r\n<br />");
                              $return_echo["Scheduled"] = $reportRequestInfo->getScheduled();
                          }
                          // add end
                          if ($reportRequestInfo->isSetSubmittedDate())
                          {
//                              echo("                    SubmittedDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["SubmittedDate"] = $reportRequestInfo->getSubmittedDate()->format(DATE_FORMAT);
                          }
                          if ($reportRequestInfo->isSetReportProcessingStatus())
                          {
//                              echo("                    ReportProcessingStatus\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getReportProcessingStatus() . "\r\n<br />");
                              $return_echo["ReportProcessingStatus"] = $reportRequestInfo->getReportProcessingStatus();
                          }
                          // add start
                          if ($reportRequestInfo->isSetGeneratedReportId())
                          {
//                              echo("                    GeneratedReportId\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getGeneratedReportId() . "\r\n<br />");
                              $return_echo["GeneratedReportId"] = $reportRequestInfo->getGeneratedReportId();
                          }
                          if ($reportRequestInfo->isSetStartedProcessingDate())
                          {
//                              echo("                    StartedProcessingDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getStartedProcessingDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["StartedProcessingDate"] = $reportRequestInfo->getStartedProcessingDate()->format(DATE_FORMAT);
                          }
                          if ($reportRequestInfo->isSetCompletedDate())
                          {
//                              echo("                    CompletedDate\r\n<br />");
//                              echo("                        " . $reportRequestInfo->getCompletedDate()->format(DATE_FORMAT) . "\r\n<br />");
                              $return_echo["CompletedDate"] = $reportRequestInfo->getCompletedDate()->format(DATE_FORMAT);
                          }
                          // add end

                    }
                }
                if ($response->isSetResponseMetadata()) {
//                    echo("            ResponseMetadata\r\n<br />");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId())
                    {
//                        echo("                RequestId\r\n<br />");
//                        echo("                    " . $responseMetadata->getRequestId() . "\r\n<br />");
                        $return_echo["RequestId"] = $responseMetadata->getRequestId();
                    }
                }

//                echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\r\n<br />");
                $return_echo["ResponseHeaderMetadata"] = $response->getResponseHeaderMetadata();

		return $return_echo;

     } catch (MarketplaceWebService_Exception $ex) {
/*
         echo("Caught Exception: " . $ex->getMessage() . "\r\n<br />");
         echo("Response Status Code: " . $ex->getStatusCode() . "\r\n<br />");
         echo("Error Code: " . $ex->getErrorCode() . "\r\n<br />");
         echo("Error Type: " . $ex->getErrorType() . "\r\n<br />");
         echo("Request ID: " . $ex->getRequestId() . "\r\n<br />");
         echo("XML: " . $ex->getXML() . "\r\n<br />");
         echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\r\n<br />");
*/
        $return_echo["function"] = "invokeGetReportRequestList";
        $return_echo["Caught_Exception"] = $ex->getMessage();
        $return_echo["Response_Status_Code"] = $ex->getStatusCode();
        $return_echo["Error_Code"] = $ex->getErrorCode();
        $return_echo["Error_Type"] = $ex->getErrorType();
        $return_echo["Request_ID"] = $ex->getRequestId();
        $return_echo["XML"] = $ex->getXML();
        $return_echo["ResponseHeaderMetadata"] = $ex->getResponseHeaderMetadata();
        $return_echo["message"] = "Delay 2 minutes and trying the same Request";
        func_print_r($return_echo);
        return $return_echo;

     }
 }

  function invokeGetReportList(MarketplaceWebService_Interface $service, $request)
  {
      try {
              $response = $service->getReportList($request);

$response_arr["ReportId"] = array();

                echo ("Service Response\r\n<br />");
                echo ("=============================================================================\r\n<br />");

                echo("        GetReportListResponse\r\n<br />");
                if ($response->isSetGetReportListResult()) {
                    echo("            GetReportListResult\r\n<br />");
                    $getReportListResult = $response->getGetReportListResult();
                    if ($getReportListResult->isSetNextToken())
                    {
                        echo("                NextToken\r\n<br />");
                        echo("                    " . $getReportListResult->getNextToken() . "\r\n<br />");
                    }
                    if ($getReportListResult->isSetHasNext())
                    {
                        echo("                HasNext\r\n<br />");
                        echo("                    " . $getReportListResult->getHasNext() . "\r\n<br />");
                    }
                    $reportInfoList = $getReportListResult->getReportInfoList();
                    foreach ($reportInfoList as $reportInfo) {
                        echo("                ReportInfo\r\n<br />");
                        if ($reportInfo->isSetReportId())
                        {
                            echo("                    ReportId\r\n<br />");
                            echo("                        " . $reportInfo->getReportId() . "\r\n<br />");
				$response_arr["ReportId"][] = $reportInfo->getReportId();
                        }
                        if ($reportInfo->isSetReportType())
                        {
                            echo("                    ReportType\r\n<br />");
                            echo("                        " . $reportInfo->getReportType() . "\r\n<br />");
                        }
                        if ($reportInfo->isSetReportRequestId())
                        {
                            echo("                    ReportRequestId\r\n<br />");
                            echo("                        " . $reportInfo->getReportRequestId() . "\r\n<br />");
                        }
                        if ($reportInfo->isSetAvailableDate())
                        {
                            echo("                    AvailableDate\r\n<br />");
                            echo("                        " . $reportInfo->getAvailableDate()->format(DATE_FORMAT) . "\r\n<br />");
                        }
                        if ($reportInfo->isSetAcknowledged())
                        {
                            echo("                    Acknowledged\r\n<br />");
                            echo("                        " . $reportInfo->getAcknowledged() . "\r\n<br />");
                        }
                        if ($reportInfo->isSetAcknowledgedDate())
                        {
                            echo("                    AcknowledgedDate\r\n<br />");
                            echo("                        " . $reportInfo->getAcknowledgedDate()->format(DATE_FORMAT) . "\r\n<br />");
                        }
                    }
                }
                if ($response->isSetResponseMetadata()) {
                    echo("            ResponseMetadata\r\n<br />");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId())
                    {
                        echo("                RequestId\r\n<br />");
                        echo("                    " . $responseMetadata->getRequestId() . "\r\n<br />");
                    }
                }

                echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\r\n<br />");

	return $response_arr;

     } catch (MarketplaceWebService_Exception $ex) {
         echo("Caught Exception: " . $ex->getMessage() . "\r\n<br />");
         echo("Response Status Code: " . $ex->getStatusCode() . "\r\n<br />");
         echo("Error Code: " . $ex->getErrorCode() . "\r\n<br />");
         echo("Error Type: " . $ex->getErrorType() . "\r\n<br />");
         echo("Request ID: " . $ex->getRequestId() . "\r\n<br />");
         echo("XML: " . $ex->getXML() . "\r\n<br />");
         echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\r\n<br />");
     }
 }

  function invokeUpdateReportAcknowledgements(MarketplaceWebService_Interface $service, $request)
  {
      try {
              $response = $service->updateReportAcknowledgements($request);

                echo ("Service Response\r\n<br />");
                echo ("=============================================================================\r\n<br />");

                echo("        UpdateReportAcknowledgementsResponse\r\n<br />");
                if ($response->isSetUpdateReportAcknowledgementsResult()) {
                    echo("            UpdateReportAcknowledgementsResult\r\n<br />");
                    $updateReportAcknowledgementsResult = $response->getUpdateReportAcknowledgementsResult();
                    if ($updateReportAcknowledgementsResult->isSetCount())
                    {
                        echo("                Count\r\n<br />");
                        echo("                    " . $updateReportAcknowledgementsResult->getCount() . "\r\n<br />");
                    }
//                    $reportInfoList = $updateReportAcknowledgementsResult->getReportInfo();

		    if (!empty($reportInfoList) && is_array($reportInfoList))
                    foreach ($reportInfoList as $reportInfo) {
                        echo("                ReportInfo\r\n<br />");
                        if ($reportInfo->isSetReportId())
                        {
                            echo("                    ReportId\r\n<br />");
                            echo("                        " . $reportInfo->getReportId() . "\r\n<br />");
                        }
                        if ($reportInfo->isSetReportType())
                        {
                            echo("                    ReportType\r\n<br />");
                            echo("                        " . $reportInfo->getReportType() . "\r\n<br />");
                        }
                        if ($reportInfo->isSetReportRequestId())
                        {
                            echo("                    ReportRequestId\r\n<br />");
                            echo("                        " . $reportInfo->getReportRequestId() . "\r\n<br />");
                        }
                        if ($reportInfo->isSetAvailableDate())
                        {
                            echo("                    AvailableDate\r\n<br />");
                            echo("                        " . $reportInfo->getAvailableDate()->format(DATE_FORMAT) . "\r\n<br />");
                        }
                        if ($reportInfo->isSetAcknowledged())
                        {
                            echo("                    Acknowledged\r\n<br />");
                            echo("                        " . $reportInfo->getAcknowledged() . "\r\n<br />");
                        }
                        if ($reportInfo->isSetAcknowledgedDate())
                        {
                            echo("                    AcknowledgedDate\r\n<br />");
                            echo("                        " . $reportInfo->getAcknowledgedDate()->format(DATE_FORMAT) . "\r\n<br />");
                        }
                    }
                }
                if ($response->isSetResponseMetadata()) {
                    echo("            ResponseMetadata\r\n<br />");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId())
                    {
                        echo("                RequestId\r\n<br />");
                        echo("                    " . $responseMetadata->getRequestId() . "\r\n<br />");
                    }
                }

                echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "\r\n<br />");
     } catch (MarketplaceWebService_Exception $ex) {
         echo("Caught Exception: " . $ex->getMessage() . "\r\n<br />");
         echo("Response Status Code: " . $ex->getStatusCode() . "\r\n<br />");
         echo("Error Code: " . $ex->getErrorCode() . "\r\n<br />");
         echo("Error Type: " . $ex->getErrorType() . "\r\n<br />");
         echo("Request ID: " . $ex->getRequestId() . "\r\n<br />");
         echo("XML: " . $ex->getXML() . "\r\n<br />");
         echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "\r\n<br />");
     }
 }



$a_config = array (
  'ServiceURL' => "https://mws.amazonservices.com",
  'ProxyHost' => null,
  'ProxyPort' => -1,
  'MaxErrorRetry' => 3,
);

$oAmazon = new \Xcart\AmazonMWS();

 $service = new MarketplaceWebService_Client(
     AWS_ACCESS_KEY_ID,
     AWS_SECRET_ACCESS_KEY,
     $a_config,
     APPLICATION_NAME,
     APPLICATION_VERSION);



$marketplaceIdArray = array("Id" => array('ATVPDKIKX0DER'));
