<?php

  /**
   * A small HTTP client over curl, for the sign-in providers' APIs and other REST services
   */
  class Rest
  {

    private $URL;
    private $Method;
    private $Data;
    private $Headers;
    private $Response;
    private $Error;
    private array $Settings;
    private mixed $ResponseHeaders;

    public function __construct($URL)
    {
      $this->URL = $URL;
      $this->Accept('application/json');
      $this->ContentType('application/json');
      $this->Settings = [
        CURLOPT_HEADER => 1,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_FRESH_CONNECT => 1,
        CURLOPT_FORBID_REUSE => 1,
        CURLOPT_VERBOSE => 1,
      ];
    }

    public function Accept(?string $Setting = null): string
    {
      return $this->SetHeader('Accept', $Setting);
    }

    public function SetHeader($Key, $Value): string
    {
      if (!is_null($Value))
      {
        if (empty($Value))
        {
          unset ($this->Headers[$Key]);
        }
        else
        {
          $this->Headers[$Key] = $Value;
        }
      }
      return $this->Headers[$Key] ?? '';
    }

    public function ContentType(?string $Setting = null): string
    {
      return $this->SetHeader('Content-Type', $Setting);
    }

    public function Bearer(string $Token): void
    {
      $this->SetHeader('Authorization', 'Bearer ' . $Token);
    }

    public function Get($QueryData = []): string|array
    {
      $this->Method = 'GET';
      $Options = [
        CURLOPT_URL => $this->URI($QueryData),
      ];
      return $this->Fetch($Options);
    }

    private function URI($QueryData = []): string
    {
      return $this->URL . (strpos($this->URL, '?') === false ? '?' : '') . http_build_query($QueryData);
    }

    private function Fetch($Options = []): string|array
    {
      $ch = curl_init();
      $this->Settings[CURLOPT_HTTPHEADER] = $this->Headers();
      curl_setopt_array($ch, ($Options + $this->Settings));
      $Response = curl_exec($ch);

      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $this->ResponseHeaders = substr($Response, 0, $header_size);
      $this->Response = substr($Response, $header_size);
//      $result['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//      $result['last_url'] = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);


      $this->Error = curl_error($ch);
      curl_close($ch);

      if (empty($this->Response) || $this->Response === true)
      {
        trigger_error($this->Error, E_USER_ERROR);
        return '';
      }
      return ($this->Accept() == 'application/json' ? json_decode($this->Response, true) : $this->Response);
    }

    private function Headers(): array|bool
    {
      $Result = [];
      foreach ($this->Headers as $Key => $Value)
      {
        $Result[] = $Key . ': ' . $Value;
      }
      return empty($Result) ? false : $Result;
    }

    public function Post($Data = [], $QueryData = []): string|array
    {
      $this->Method = 'POST';
      $this->Data = $Data;

      $Options = [
        CURLOPT_URL => $this->URI($QueryData),
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => $this->FormatData($Data)
      ];

      return $this->Fetch($Options);
    }

    private function FormatData($Data): string
    {
      switch ($this->ContentType())
      {
        case 'application/json':
          return json_encode($Data);
        case 'application/x-www-form-urlencoded':
        default:
          return http_build_query($Data);
      }
    }

    public function ResponseHeaders()
    {
      $Response = [];
      foreach (explode("\r\n", $this->ResponseHeaders) as $Line)
      {
        if (preg_match('/^([^:]+): (.+)$/', $Line, $matches))
        {
          $Response[$matches[1]] = $matches[2];
        }
        elseif (preg_match('/^([^ ]*) (.+)$/', $Line, $matches))
        {
          $Response[$matches[1]] = $matches[2];
        }

      }

      return $Response;
    }


  }
