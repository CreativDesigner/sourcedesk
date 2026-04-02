<?php
// Class for handling invoice items
class InvoiceItem
{
    private $info;
    private $invoice;

    public function __construct()
    {
        $this->info = new stdClass;
        $this->invoice = new Invoice;
    }

    public function load($id)
    {
        global $db, $CFG;

        $id = intval($id);
        $sql = $db->query("SELECT * FROM invoiceitems WHERE ID = " . $id);
        if ($sql->num_rows != 1) {
            return false;
        }

        $info = $sql->fetch_object();

        foreach ($info as $k => $v) {
            $this->info->$k = $v;
        }

        $this->invoice->load($this->info->invoice);
    }

    public function save()
    {
        global $db, $CFG;

        if (!empty($this->info->ID)) {
            $str = "";
            foreach ($this->info as $k => $v) {
                $str .= "`$k` = '" . $db->real_escape_string($v) . "', ";
            }

            $str = rtrim($str, ", ");
            $db->query("UPDATE invoiceitems SET $str WHERE ID = " . $this->info->ID . " LIMIT 1");
        } else {
            if ($CFG['NO_INVOICING']) {
                return;
            }

            $fields = $values = "";
            foreach ($this->info as $k => $v) {
                $fields .= "`$k`, ";
                $values .= "'" . $db->real_escape_string($v) . "', ";
            }
            $fields = rtrim($fields, ", ");
            $values = rtrim($values, ", ");
            $db->query("INSERT INTO invoiceitems ($fields) VALUES ($values)");
            $this->info->ID = $db->insert_id;
        }
    }

    public function delete()
    {
        global $db, $CFG;

        if (empty($this->info->ID)) {
            return false;
        }

        $db->query("DELETE FROM invoiceitems WHERE ID = " . $this->info->ID);
    }

    public function getQty()
    {
        return isset($this->info->qty) ? doubleval($this->info->qty) : 1;
    }

    public function getUnit()
    {
        return @$this->info->unit ?: "x";
    }

    public function getInvoice()
    {
        return $this->invoice;
    }

    public function getDescription()
    {
        return @$this->info->description ?: "";
    }

    public function getAmount()
    {
        return @$this->info->amount ?: "0.00";
    }

    public function getRelid()
    {
        return @$this->info->relid ?: "";
    }

    public function getTax()
    {
        return @$this->info->tax ? true : false;
    }

    public function setInvoice(Invoice $invoice)
    {
        $this->info->invoice = $invoice->getId();
        $this->invoice = $invoice;
    }

    public function setDescription($description)
    {
        $this->info->description = $description;
    }

    public function setAmount($amount)
    {
        $this->info->amount = $amount;
    }

    public function setRelid($relid)
    {
        $this->info->relid = $relid;
    }

    public function setRecurring($id)
    {
        $this->info->recurring = $id;
    }

    public function setTax($t)
    {
        $this->info->tax = intval($t);
    }

    public function setQty($q)
    {
        $this->info->qty = round(doubleval($q), 2);
    }

    public function setUnit($u)
    {
        $this->info->unit = strval($u);
    }

    public function getNet()
    {
        return $this->getTaxRate() == 0 || !$this->getTax() ? $this->getGross() : round($this->getGross() * 100 / (100 + $this->getTaxRate()), 2);
    }

    public function getTaxRate() {
        return $this->invoice->getTaxRate();
    }

    public function getGross()
    {
        return $this->getAmount();
    }

    public function getTaxAmount() {
        return $this->getGross() - $this->getNet();
    }

    public static function getByRelid($relid)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT ID FROM invoiceitems WHERE relid = " . intval($relid));
        $items = [];

        while ($row = $sql->fetch_object()) {
            $inv = new InvoiceItem;
            $inv->load($row->ID);
            array_push($items, $inv);
        }

        if (count($items) == 0) {
            return false;
        } else if (count($items) == 1) {
            return $items[0];
        } else {
            return $items;
        }
    }

    public function getInfo()
    {
        return $this->info;
    }
}
