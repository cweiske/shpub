<?php
namespace shpub;

class CurlPrinter
{
    public function show($httpReq, $uploadsInfo = [], $dedicatedBody = false)
    {
        $command = 'curl';
        if ($httpReq->getMethod() != 'GET') {
            $command .= ' -X ' . $httpReq->getMethod();
        }
        foreach ($httpReq->getHeaders() as $key => $val) {
            $caseKey = implode('-', array_map('ucfirst', explode('-', $key)));
            $command .= ' -H ' . escapeshellarg($caseKey . ': ' . $val);
        }

        $postParams = $httpReq->getPostParams();

        if (count($uploadsInfo) == 0) {
            foreach ($postParams as $k => $v) {
                if (!is_array($v)) {
                    $command .= ' -d ' . escapeshellarg($k . '=' . $v);
                } else {
                    foreach ($v as $ak => $av) {
                        $command .= ' -d ' . escapeshellarg(
                            $k . '[' . $ak . ']=' . $av
                        );
                    }
                }
            }
        } else {
            foreach ($postParams as $k => $v) {
                $command .= ' -F ' . escapeshellarg($k . '=' . $v);
            }
            foreach ($uploadsInfo as $fieldName => $fileName) {
                if (!is_array($fileName)) {
                    $command .= ' -F ' . escapeshellarg(
                        $fieldName . '=@' . $fileName
                    );
                } else {
                    foreach ($fileName as $k => $realFilename) {
                        $command .= ' -F ' . escapeshellarg(
                            $fieldName . '[' . $k . ']=@' . $realFilename
                        );
                    }
                }
            }
        }

        if ($dedicatedBody) {
            $command .= ' --data ' . escapeshellarg($httpReq->getBody());
        }

        $command .= ' ' . escapeshellarg((string) $httpReq->getUrl());

        Log::msg($command);
    }
}
?>
