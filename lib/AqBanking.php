<?php
// Wrapper for AqBanking

class AqBanking
{
    private $path, $pinFile, $saldo, $transactions;

    public function __construct($path, $pinFile)
    {
        $this->path = $path;
        $this->pinFile = $pinFile;
    }

    public function fetchData($bankCode, $account)
    {
        $file = __DIR__ . "/.$bankCode-$account.ctx";
        shell_exec("{$this->path} -n -P {$this->pinFile} request -b $bankCode -a $account -c $file --transactions");
        if (!file_exists($file)) {
            return false;
        }

        $saldo = shell_exec("{$this->path} listbal -b $bankCode -a $account -c $file");
        $ex = explode("\t", $saldo);
        $this->saldo = doubleval($ex[11]);

        $this->transactions = [];
        $transactions = shell_exec("{$this->path} listtrans -b $bankCode -a $account -c $file");

        unlink($file);

        $ex = explode("\n", $transactions);
        array_shift($ex);
        foreach ($ex as $line) {
            if (empty($line)) {
                continue;
            }

            $cols = explode(";", $line);
            foreach ($cols as &$v) {
                $v = trim($v, '"');
            }

            array_push($this->transactions, $cols);
        }
    }

    public function getSaldo()
    {
        return $this->saldo;
    }

    public function getTransactions()
    {
        return array_reverse($this->transactions);
    }
}
