<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 *
 * Modified by __root__ on 17-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Deconf\SEIWP\Google\Service\GoogleAnalyticsAdmin;

class GoogleAnalyticsAdminV1alphaBatchDeleteUserLinksRequest extends \Deconf\SEIWP\Google\Collection
{
  protected $collection_key = 'requests';
  protected $requestsType = GoogleAnalyticsAdminV1alphaDeleteUserLinkRequest::class;
  protected $requestsDataType = 'array';

  /**
   * @param GoogleAnalyticsAdminV1alphaDeleteUserLinkRequest[]
   */
  public function setRequests($requests)
  {
    $this->requests = $requests;
  }
  /**
   * @return GoogleAnalyticsAdminV1alphaDeleteUserLinkRequest[]
   */
  public function getRequests()
  {
    return $this->requests;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleAnalyticsAdminV1alphaBatchDeleteUserLinksRequest::class, 'Google_Service_GoogleAnalyticsAdmin_GoogleAnalyticsAdminV1alphaBatchDeleteUserLinksRequest');
