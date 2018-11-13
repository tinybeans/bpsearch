<?php
/**
 * BPSearch クラス
 *
 * @package   BPSearch
 * @copyright Copyright (c) Tomohiro Okuwaki, bit part LLC
 * @link      https://bit-part.net
 */

class BPSearch
{

  public $timeStart = 0;
  public $response = [
    'totalResults' => 0,
    'items' => [],
  ];
  public $requestedParams = null;
  private $dataDirPath = '';
  private $config = [
    // データを格納しているディレクトリのパス
    'dataDirPath' => null,
    // キャッシュを保存するディレクトリのパス
    'cacheDirPath' => null,
    // limit, offset の初期値を設定
    'limit'  => 20,
    'offset' => 0,
    // limit, offset にかかわらずすべての結果を返す場合は true をセット
    'return_all' => false,
    // フィルター検索を許可するパラメータ（ `search` `rand` `from` `to` は特殊なパラメータのためフィルターに利用できません）
    'filters' => [
      'id' => 'eq',
      'title' => 'like',
      'url' => 'like',
      'keywords' => 'eq',
      'categoryId' => 'eq',
      'categoryIds' => 'eq',
      'categoryLabel' => 'eq',
      'tagIds' => 'eq',
      'year' => 'eq',
      'month' => 'eq',
      'date' => 'eq',
      'day' => 'eq',
      'datetime' => 'eq',
    ],
    // パラメータマッピング
    'paramMapping' => [
      'from' => 'datetime',
      'to' => 'datetime',
    ],
    // ソートの基準となるパラメータとソート順を初期値（パラメータに `sortBy` または `sortOrder` がない場合は表示用データの順）
    'sortBy' => 'datetime',
    'sortOrder' => 'descend', // 'ascend' or 'descend'
    // デバッグモードにする場合は true をセット
    'devMode' => false,
    // 処理時間を計測する場合は true をセット
    'returnTime' => false,
    // 実行ファイルの URL を JSON に含める場合は true をセット
    'includeScriptUrl' => true,
    // リファラの URL を JSON に含める場合は true をセット
    'includeRefererUrl' => true,
    // ページネーションの情報を結果の JSON に含める場合は true をセット
    'includePagination' => true,
    // ページネーションに表示する最大数（奇数をセット）
    'viewPagesLimit' => 3, // 3, 5, 7, 9 ...
  ];


  /**
   * Initialisation
   *
   * @param $config
   * @param $devMode
   */
  public function __construct($config, $devMode)
  {
    if (!empty($config) && is_array($config)) {
      $this->config = array_merge($this->config, $config);
    }
    $this->requestedParams = $_GET;
    $this->init();
  }


  /**
   * devMode が true の時にデバッグ用のメッセージを出力する。
   *
   * @param $varName
   * @param $var
   */
  public function devModeMessage($varName, $var)
  {
    if ($this->config['devMode']) {
      $value = print_r($var, true);
      echo "--------------------------------------\n";
      echo "$varName\n";
      echo "$value\n";
      echo "--------------------------------------\n";
    }
  }


  /**
   * devMode が true の時にメモリ使用量を出力する。
   */
  public function devModeDumpMemory()
  {
    if ($this->config['devMode']) {
      static $initialMemoryUse = null;
      if ( $initialMemoryUse === null ) {
        $initialMemoryUse = memory_get_usage();
      }
      $value = print_r(number_format(memory_get_usage() - $initialMemoryUse), true);
      $this->devModeMessage('Memory', $value);
    }
  }


  /**
   * $url に $page（現在のページ数）と $limit 数を渡すと自動で offset を算出して、
   * limit と offset を付与した URL を返す。
   *
   * @param $url
   * @param $page
   * @param $limit
   * @return string
   */
  public function limitOffsetAddUrl($url, $page, $limit)
  {
    return $url . (strpos($url, '?') === false ? '?' : '&') . 'limit=' . $limit . '&offset=' . (($page - 1) * $limit);
  }


  /**
   * URL 文字列から各種情報を返す。
   *
   * @param $urlString
   * @return array
   */
  public function sanitizeUrl($urlString)
  {
    $url = parse_url($urlString);
    $sanitize_query = [];
    if (isset($url['query'])) {
      $query_pairs = explode('&', $url['query']);
      foreach ($query_pairs as $query_string) {
        $query = explode('=', $query_string);
        if ($query[0] != 'limit' && $query[0] != 'offset') {
          $sanitize_query[] = $query_string;
        }
      }
    }
    $out = [
      'original' => $urlString,
      'parse' => $url,
      'sanitizeQuery' => implode('&', $sanitize_query),
    ];
    if (!empty($out['sanitizeQuery'])) {
      $out['sanitizeQuery'] = '?' . $out['sanitizeQuery'];
    }
    return $out;
  }


  /**
   * 引数で渡された値を UTF-8 に変換して返す
   *
   * @param $param
   * @return string
   */
  public function getParamValue($param)
  {
    $param = (string)$param;
    if (ctype_digit($param)) {
      return $param;
    }
    if (empty($param)) {
      return '';
    }
    if (mb_detect_encoding($param) !== 'UTF-8') {
      $param = mb_convert_encoding($param, 'UTF-8', mb_detect_encoding($param));
    }
    return $param;
  }


  /**
   * limit パラメータの値、なければ config の limit の値を返す。
   *
   * @return int|mixed
   */
  public function getLimitValue()
  {
    if (empty($this->requestedParams['limit'])) {
      return $this->config['limit'];
    }
    return (int)$this->requestedParams['limit'];
  }


  /**
   * offset パラメータの値、なければ config の offset の値を返す。
   *
   * @return int|mixed
   */
  public function getOffsetValue()
  {
    if (empty($this->requestedParams['offset'])) {
      return $this->config['offset'];
    }
    return (int)$this->requestedParams['offset'];
  }


  /**
   * rand=キー (or rand=1) が指定された場合は limit * 2 の数だけランダムに記事を取得しておく。
   * あらかじめ all.json の各 item に rand プロパティを設定する必要あり
   * (e.g. rand: 1 or rand: 'キー')
   *
   * @param $data
   * @return array
   */
  public function getRandomEntries($data)
  {
    $randomData = [];
    if (empty($data)) {
      return $randomData;
    }
    $limit = $this->getLimitValue();
    if (!empty($this->requestedParams['rand'])) {
      $randParam = $this->requestedParams['rand'];
      $doubledLimit = $limit * 2;
      $randLoopCount = 0;
      while (count($randomData) < $doubledLimit || $randLoopCount < count($data)) {
        $key = array_rand($data);
        if (in_array($randParam, $data[$key]['rand'])) {
          $randomData[$key] = $data[$key];
        }
        $randLoopCount++;
      }
    }
    return $randomData;
  }


  /**
   * キャッシュファイル名とパスを取得する。
   *
   * @return string
   */
  public function createCacheFileName()
  {
    $query = $_SERVER['QUERY_STRING'];
    $query = preg_replace('/&?_=[^&]+/u', '', $query);
    $cacheDirPath = $this->config['cacheDirPath'] ? $this->config['cacheDirPath'] : realpath('cache');
    $cacheFileName = $cacheDirPath . DIRECTORY_SEPARATOR . md5($query);
    $this->devModeMessage('キャッシュファイル名', $cacheFileName);
    return $cacheFileName;
  }


  /**
   * キャッシュファイルがある場合はキャッシュファイルを取得して表示する。
   *
   * @return bool
   */
  public function getCache()
  {
    if (empty($_SERVER['QUERY_STRING']) || empty($this->requestedParams['cache'])) {
      return false;
    }
    $cacheFileName = $this->createCacheFileName();
    if (!file_exists($cacheFileName)) {
      return false;
    }
    if ($cache = file_get_contents($cacheFileName)) {
      echo $cache;
      exit();
    }
  }


  /**
   * キャッシュファイルを書き出す。
   *
   * @return bool
   */
  public function setCache()
  {
    if (empty($this->requestedParams['cache'])) {
      return false;
    }
    $cacheFileName = $this->createCacheFileName();
    $content = json_encode($this->response, JSON_UNESCAPED_UNICODE);
    file_put_contents($cacheFileName, $content);
  }


  /**
   * キーワード検索を実行する。
   *
   * search パラメータが empty ではない = search パラメータが下記の値でない場合
   * - "" (an empty string)
   * - 0 (0 as an integer)
   * - 0.0 (0 as a float)
   * - "0" (0 as a string)
   * - NULL
   * - FALSE
   * - array() (an empty array)
   * - $var; (a variable declared, but without a value)
   *
   * @param $data
   * @return array
   */
  public function search($data)
  {
    $entries = [];
    if (empty($this->requestedParams['search'])) {
      foreach ($data as $entry_id => $entry) {
        $entries[] = $entry;
      }
    }
    else {
      // search パラメータの値を取得
      $search = $this->getParamValue($this->requestedParams['search']);

      // search パラメータの値の中で、半角・全角スペース、+ が連続している場合、半角スペース一つにする
      $search = preg_replace('/[　\s\+]+/u', ' ', $search);

      // search パラメータの値を、半角・全角スペースで分割して配列にする
      $keywords = preg_split('/ /u', $search);
      $keywords_count = count($keywords);
      $this->devModeMessage('キーワード（$search）', $search);
      $this->devModeMessage('キーワード（$keywords）', $keywords);
      $this->devModeMessage('キーワード数', $keywords_count);

      // 検索用コマンドを作成
      $cmd = '';
      $tmpFileNames = [];
      foreach ($keywords as $keyword) {
        $tmpFileName = tempnam(sys_get_temp_dir(), 'grep-param-');
        $tmpFileNames[] = $tmpFileName;
        $handle = fopen($tmpFileName, 'w');
        fwrite($handle, $keyword);
        fclose($handle);
        if (empty($cmd)) {
          $cmd = 'grep -i -f "' . $tmpFileName . '" ' . $this->dataDirPath . '/all.txt';
        } else {
          $cmd .= ' | grep -i -f "' . $tmpFileName . '"';
        }
      }
      if ($cmd != '') {
        $cmd .= ' | awk \'{print $1}\'';
        $res = chop(shell_exec($cmd));
        $this->devModeMessage('実行コマンド', $cmd);
        if ($res) {
          $ids = explode("\n", $res);
          unset($res);
          array_unique($ids);
          $this->devModeMessage('該当ID', implode(', ', $ids));
          foreach ($ids as $id) {
            if (array_key_exists('e' . $id, $data)) {
              $entries[] = $data['e' . $id];
            }
          }
        }
        else {
          if ($this->config['returnTime']) {
            $this->response['processingTime'] = (microtime(true) - $this->timeStart) . '秒';
          }
          $this->setCache();
          $this->response();
        }
      }
      foreach ($tmpFileNames as $tmpFileName) {
        unlink($tmpFileName);
      }
      $this->devModeMessage('キーワード検索後の記事数', count($entries));
    }
    return $entries;
  }


  /**
   * all.json をフィルターして返す。
   *
   * @param $entries
   * @return array
   */
  public function filterSearch($entries)
  {
    if (empty($entries) || empty($this->requestedParams) || empty($this->config['filters'])) {
      return $entries;
    }

    $filterRules = $this->config['filters'];

    // 特別なパラメータを追加・除外してフィルターの条件をセット
    $filterRules['from'] = 'ge';
    $filterRules['to'] = 'lt';
    $removeFilter = ['search', 'rand', 'sortBy', 'sortOder', 'limit', 'offset', 'page'];
    foreach ($removeFilter as $key) {
      if (isset($filterRules[$key])) {
        unset($filterRules[$key]);
      }
    }
    $this->devModeMessage('$filterRules', $filterRules);
    // END 特別なパラメータを追加・除外してフィルターの条件をセット


    // 特定のフィールドでの絞り込み用の $filters 変数をセット
    $filters = [];
    foreach ($this->requestedParams as $param => $value) {
      if (!isset($filterRules[$param])) {
        continue;
      }
      if (is_array($value)) {
        $filters[$param] = [];
        foreach ($value as $eachValue) {
          $filters[$param][] = $this->getParamValue($eachValue);
        }
      }
      else {
        $filters[$param] = $this->getParamValue($value);
      }
    }
    $this->devModeMessage('$filters', $filters);
    // END 特定のフィールドでの絞り込み用の $filters 変数をセット

    // 絞り込みを実行
    if (!empty($filters)) {

      $filtersCount = count($filters);
      foreach ($entries as $id => $entry) {

        // マッチした数を入れる変数を初期化
        $match = 0;
        foreach ($filters as $key => $value) {

          // パラメータマッピングからキーを取得
          $mappedKey = isset($this->config['paramMapping'][$key]) ? $this->config['paramMapping'][$key] : $key;

          // $mappedKey が $entry の中に存在する場合だけ処理する
          if (!isset($entry[$mappedKey])) {
            continue;
          }

          // パラメータとJSONの値の両方が配列の場合
          // 例：tagsIds[]=111&tagsIds[]=222 の場合は tagsIds が 111 または 222 を含めばヒット（ OR 検索）
          //    この検索の場合は JSON に `relevance` キーが追加され関連順に並べることが可能
          if (is_array($value) && is_array($entry[$mappedKey])) {
            $relevance = 0;
            foreach ($value as $eachValue) {
              if (in_array($eachValue, $entry[$mappedKey])) {
                $relevance++;
              }
            }
            if ($relevance) {
              $match++;
              if (isset($entry['relevance'])) {
                // 参照渡しにしていないため
                $entries[$id]['relevance'] += $relevance;
              }
              else {
                $entries[$id]['relevance'] = $relevance;
              }
            }
          }
          // パラメータが配列、JSONの値は文字列の場合
          // 例：category[]=blog&category[]=news の場合は category が blog か news であればヒット
          elseif (is_array($value)) {
            if (in_array($entry[$mappedKey], $value)) {
              if (isset($filterRules[$key]) && $filterRules[$key] === 'not') {
                // ヒットさせない
              }
              else {
                $match++;
              }
            }
            elseif (isset($filterRules[$key]) && $filterRules[$key] === 'not') {
              $match++;
            }
          }
          // パラメータが文字列、JSONの値が配列の場合
          // 例：categoryIds=123 で $entry['categoryIds] = [] の場合は categoryIds が JSON の categoryIds の配列の中にあればヒット
          elseif (is_array($entry[$mappedKey])) {
            if (in_array($value, $entry[$mappedKey])) {
              if (isset($filterRules[$key]) && $filterRules[$key] === 'not') {
                // ヒットさせない
              }
              else {
                $match++;
              }
            }
            elseif (isset($filterRules[$key]) && $filterRules[$key] === 'not') {
              $match++;
            }
          }
          // その他の文字列検索
          elseif (!empty($filterRules) && isset($filterRules[$key])) {
            // like 検索の場合（filtersCond パラメータで "like" を指定した場合）
            if ($filterRules[$key] === 'like') {
              if (stripos($entry[$mappedKey], $value) !== false) {
                $match++;
              }
            }
            // lt 検索の場合（パラメータの値よりも小さいものがヒットする場合）
            elseif ($filterRules[$key] === 'lt') {
              if ($entry[$mappedKey] < $value) {
                $match++;
              }
            }
            // le 検索の場合（パラメータの値よりも小さいか等しいものがヒットする場合）
            elseif ($filterRules[$key] === 'le') {
              if ($entry[$mappedKey] <= $value) {
                $match++;
              }
            }
            // gt 検索の場合（パラメータの値よりも大きいものがヒットする場合）
            elseif ($filterRules[$key] === 'gt') {
              if ($entry[$mappedKey] > $value) {
                $match++;
              }
            }
            // ge 検索の場合（パラメータの値よりも大きいか等しいものがヒットする場合）
            elseif ($filterRules[$key] === 'ge') {
              if ($entry[$mappedKey] >= $value) {
                $match++;
              }
            }
            // not 検索の場合（パラメータと一致するものは除外）
            elseif ($filterRules[$key] === 'not') {
              if ($entry[$mappedKey] !== $value) {
                $match++;
              }
            }
            // 完全一致検索の場合（初期値）
            elseif ($entry[$mappedKey] === $value) {
              $match++;
            }
          }
          // 完全一致検索の場合（初期値）
          elseif ($entry[$mappedKey] === $value) {
            $match++;
          }
        }
        // $filters のアイテム数とマッチ数が一致しなかったら $entries から削除
        if ($match !== $filtersCount) {
          unset($entries[$id]);
        }
      }
      $entries = array_values($entries);
    }
    /*  END 特定のフィールドによる完全一致フィルター  */
    return $entries;
  }


  /**
   * sortBy と sortOrder パラメータで配列をソートする。
   * sortBy: JSON の中のキーを指定
   * sortOrder: ascend/descend
   *
   * @param $entries
   * @return mixed
   */
  public function sortEntries($entries)
  {
    $sortBy = isset($this->requestedParams['sortBy']) ? $this->requestedParams['sortBy'] : $this->config['sortBy'];
    $sortOrder = isset($this->requestedParams['sortOrder']) ?$this->requestedParams['sortOrder'] : $this->config['sortOrder'];
    $sortBy = explode(',', $sortBy);
    $sortOrder = explode(',', $sortOrder);
    $sort = [];
    for ($i = 0, $l = count($sortBy); $i < $l; $i++) {
      if ($sortOrder[$i] === 'ascend') {
        $sortOrder[$i] = SORT_ASC;
      }
      else {
        $sortOrder[$i] = SORT_DESC;
      }
      $sort[$i] = [];
      foreach ($entries as $key => $value) {
        $sort[$i][$key] = $value[$sortBy[$i]] ? $value[$sortBy[$i]] : null;
      }
    }
    if (!empty($sort)) {
      if (count($sortBy) > 1) {
        array_multisort($sort[0], $sortOrder[0], $sort[1], $sortOrder[1], $entries);
      }
      else {
        array_multisort($sort[0], $sortOrder[0], $entries);
      }
    }
    return $entries;
  }


  /**
   * レスポンスを返却してスクリプトを終了する。
   */
  public function response()
  {
    echo json_encode($this->response, JSON_UNESCAPED_UNICODE);
    exit();
  }


  /**
   * Initialisation
   */
  public function init()
  {
    // クエリ文字列を表示
    $this->devModeMessage('QUERY_STRING', $_SERVER['QUERY_STRING']);

    // cache=1 パラメータがついていてキャッシュファイルがある場合はそのファイルの中身を返して終了
    $this->getCache();

    // ディレクトリのパスをセット
    $this->dataDirPath = $this->config['dataDirPath'] ? $this->config['dataDirPath'] : realpath('data');

    if ($this->config['returnTime']) {
      $this->timeStart = microtime(true);
    }
    $this->devModeDumpMemory();


    // パラメータに limit と offset がある場合は初期値を上書き
    $limit = $this->getLimitValue();
    $offset = $this->getOffsetValue();
    // END パラメータに limit と offset がある場合は初期値を上書き


    // 全記事の連想配列を作成
    $data = [];
    if ($json = file_get_contents($this->dataDirPath . '/all.json')) {
      $data = json_decode($json, true);
      $data = $data['items'];
    }
    $this->devModeMessage('全記事数', count($data));
    $this->devModeDumpMemory();
    // END 全記事の連想配列を作成


    // rand=キー が指定された場合は limit * 2 の数だけランダムに記事を取得しておく
    $randomData = $this->getRandomEntries($data);


    // search パラメータによるキーワード検索
    $entries = $this->search($data);
    unset($data);
    $this->devModeDumpMemory();
    // END search パラメータによるキーワード検索


    // 各種パラメータによるフィルタ
    $entries = $this->filterSearch($entries);
    $this->devModeMessage('絞込後の記事数', count($entries));

    // rand=キー が指定され、結果が limit に満たない場合はランダム記事で埋める
    if (!empty($this->requestedParams['rand']) && !empty($randomData) && count($entries) < $limit) {
      foreach ($randomData as $random_key => $random_value) {
        $entries[] = $random_value;
        if (count($entries) == $limit) {
          break;
        }
      }
    }
    // END rand=キー が指定され、結果が limit に満たない場合はランダム記事で埋める


    // ゼロ件だった場合はゼロ結果を返す
    if (count($entries) < 1) {
      if ($this->config['returnTime']) {
        $this->response['processingTime'] = (microtime(true) - $this->timeStart) . '秒';
      }
      $this->setCache();
      $this->response();
    }
    // END ゼロ件だった場合はゼロ結果を返す


    // 配列をソート
    $entries = $this->sortEntries($entries);

    /* ==================================================
        結果を出力
    ================================================== */
    // マッチした記事がある場合
    $total_results = count($entries);
    $this->response['totalResults'] = $total_results;
    // すべてを返す設定の場合
    if ($this->config['return_all']) {
      $this->response['items'] = $entries;
    }
    // offset が totalResults 以上の場合は error を返す
    elseif ($total_results <= $offset) {
      if ($this->config['returnTime']) {
        $this->response['processingTime'] = (microtime(true) - $this->timeStart) . '秒';
      }
      $this->response = [
        'error' => [
          'message' => 'offset値が検索結果件数を超えています。',
        ],
      ];
      $this->setCache();
      $this->response();
    }
    // (totalResults - offset) が limit に満たない場合は offset 位置から最後まで抜き出す
    elseif ( ($total_results - $offset) < $limit ) {
      $this->response['items'] = array_slice($entries, $offset);
    }
    // 通常の場合
    else {
      $this->response['items'] = array_slice($entries, $offset, $limit);
    }
    // 実行ファイルの URL を取得
    $scriptUrl = $this->sanitizeUrl($_SERVER['REQUEST_URI']);
    if ($this->config['includeScriptUrl']) {
      $this->response['scriptUrl'] = $scriptUrl;
    }
    // リファラの URL を取得
    $refererUrl = (!empty($_SERVER['HTTP_REFERER'])) ? $this->sanitizeUrl($_SERVER['HTTP_REFERER']) : $scriptUrl;
    if ($this->config['includeRefererUrl']) {
      $this->response['refererUrl'] = $refererUrl;
    }
    // ページネーションを JSON に追加
    if ($this->config['includePagination']) {
      // ページネーションの情報を作成
      $pagination = [
        'minPage' => 1,
        'maxPage' => ceil($total_results / $limit),
        'currentPage' => ceil(($offset + $limit) / $limit),
        'limit' => $limit,
        'offset' => $offset,
        'prevOffset' => ($limit <= $offset) ? $offset - $limit : 0,
        'nextOffset' => ($offset + $limit) <= $total_results  ? $offset + $limit : $offset,
        'totalResults' => $total_results,
      ];
      $pagination['isFirstPage'] = $pagination['currentPage'] == 1;
      $pagination['isLastPage'] = $pagination['currentPage'] == $pagination['maxPage'];
      // ページリストを作成
      $pagination['pages'] = [];
      $all_pages = range($pagination['minPage'], $pagination['maxPage']);
      foreach ($all_pages as $value) {
        $pagination['pages'][] = [
          'page' => $value,
          'url' => $this->limitOffsetAddUrl($refererUrl['sanitizeQuery'], $value, $limit),
        ];
      }
      // 前のページを作成
      if ($pagination['currentPage'] > 1) {
        $prev_page = $pagination['currentPage'] - 1;
        $pagination['prevPage'] = [
          'page' => $prev_page,
          'url' => $this->limitOffsetAddUrl($refererUrl['sanitizeQuery'], $prev_page, $limit),
        ];
      }
      // 次のページを作成
      if ($pagination['currentPage'] < $pagination['maxPage']) {
        $next_page = $pagination['currentPage'] + 1;
        $pagination['nextPage'] = [
          'page' => $next_page,
          'url' => $this->limitOffsetAddUrl($refererUrl['sanitizeQuery'], $next_page, $limit),
        ];
      }
      // 表示するページの数が決まっている場合、表示するページリストを作成
      if (!empty($this->config['viewPagesLimit'])) {
        $page_padding = floor($this->config['viewPagesLimit'] / 2);
        $pagination['page_from'] = $pagination['currentPage'] - $page_padding;
        $pagination['page_to'] = $pagination['currentPage'] + $page_padding;
        // $pagination['debug_page_from'] = $pagination['currentPage'] - $page_padding;
        // $pagination['debug_page_to'] = $pagination['currentPage'] + $page_padding;
        if ($pagination['page_from'] < 1) {
          $pagination['page_from'] = 1;
          $pagination['page_to'] = $this->config['viewPagesLimit'] < $pagination['maxPage'] ? $this->config['viewPagesLimit'] : $pagination['maxPage'];
        }
        // $pagination['debug2_page_from'] = $pagination['page_from'];
        // $pagination['debug2_page_to'] = $pagination['page_to'];
        if ($pagination['page_to'] > $pagination['maxPage']) {
          $pagination['page_to'] = $pagination['maxPage'];
          $pagination['page_from'] = $pagination['page_to'] - $this->config['viewPagesLimit'] + 1;
        }
        if ($pagination['page_from'] < 1) {
          $pagination['page_from'] = 1;
        }
        $pagination['corePages'] = [];
        $core_pages = range($pagination['page_from'], $pagination['page_to']);
        foreach ($core_pages as $value) {
          $pagination['corePages'][] = [
            'page' => $value,
            'url' => $this->limitOffsetAddUrl($refererUrl['sanitizeQuery'], $value, $limit),
          ];
        }
      }
      // 結果 JSON にセット
      $this->response['pagination'] = $pagination;
    }
    // 処理時間を結果の JSON に追加
    if ($this->config['returnTime']) {
      $this->response['processingTime'] = (microtime(true) - $this->timeStart) . '秒';
    }
    $this->setCache();
    $this->response();
    /*  END 結果を出力  */

  }
}
