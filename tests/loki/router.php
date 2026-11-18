<?php
  // A stand-in for Loki's API, for test-loki.php: canned answers, and the request written back so the test sees it
  $Path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
  parse_str((string)parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY), $Query);
  header('Content-Type: application/json');
  $Query['_path'] = $Path;
  $Echo = ['_request' => $Query];
  $Q = $Query['query'] ?? '';
  if (str_contains($Q, 'broken'))
  {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'parse error at line 1']);
    return;
  }
  if ($Path === '/loki/api/v1/query')
  {
    if (str_contains($Q, 'count_over_time') && str_contains($Q, 'topk'))
    {
      $Result = [['metric' => ['name' => 'B'], 'value' => [1, '30']], ['metric' => ['name' => 'A'], 'value' => [1, '50']]];
    }
    elseif (str_contains($Q, 'unwrap ms'))
    {
      $Result = [['metric' => ['name' => 'A'], 'value' => [1, '1200.5']], ['metric' => ['name' => 'B'], 'value' => [1, '900']], ['metric' => ['name' => 'C'], 'value' => [1, '5']]];
    }
    else
    {
      $Result = [['metric' => [], 'value' => [1, '4512']]];
    }
    echo json_encode(['status' => 'success', 'data' => ['resultType' => 'vector', 'result' => $Result]] + $Echo);
    return;
  }
  if ($Path === '/loki/api/v1/query_range')
  {
    if (str_starts_with($Q, '{'))
    {
      // Two streams, interleaved in time
      echo json_encode(['status' => 'success', 'data' => ['resultType' => 'streams', 'result' => [
        ['stream' => ['job' => 'oneweb', 'host' => 'apache1'], 'values' => [['1700000003000000000', '{"page":"/a.php","code":500,"name":"A"}'], ['1700000001000000000', 'plain text line']]],
        ['stream' => ['job' => 'oneweb', 'host' => 'apache2'], 'values' => [['1700000002500000000', '{"page":"/b.php","code":404,"name":"B"}']]],
      ]]] + $Echo);
      return;
    }
    echo json_encode(['status' => 'success', 'data' => ['resultType' => 'matrix', 'result' => [
      ['metric' => ['host' => 'apache1'], 'values' => [[1700000000, '1'], [1700000060, '2']]]]]] + $Echo);
    return;
  }
  if (preg_match('#^/loki/api/v1/label/(\w+)/values$#', $Path, $Match))
  {
    echo json_encode(['status' => 'success', 'data' => ['apache2', 'apache10', 'apache1']] + $Echo);
    return;
  }
  http_response_code(404);
  echo 'not found';
