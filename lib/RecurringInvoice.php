<?php

class RecurringInvoice
{
    public $info = null;

    public function __construct($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT * FROM invoice_items_recurring WHERE ID = " . intval($id));
        if ($sql->num_rows == 1) {
            $this->info = $sql->fetch_object();
        }

    }

    public static function getInstance($id)
    {
        $obj = new self($id);
        return $obj->info ? $obj : false;
    }

    public function getId()
    {
        return $this->info->ID;
    }

    public function getInterval()
    {
        return $this->info->period;
    }

    public function getFirst()
    {
        return $this->info->first;
    }

    public function getNext()
    {
        $date = $this->getFirst();
        if (intval($this->getInterval()) <= 0) {
            return false;
        }

        while ($date <= $this->info->last) {
            $date = date("Y-m-d", strtotime("+" . $this->getInterval(), strtotime($date)));
        }

        return $date;
    }

    public function getAmount()
    {
        return $this->info->amount;
    }

    public function getInvoicedAmount()
    {
        global $db, $CFG;
        return $db->query("SELECT SUM(amount) as s FROM invoiceitems WHERE recurring = {$this->info->ID}")->fetch_object()->s;
    }

    public function getStatus()
    {
        return $this->info->status;
    }

    public function getUser()
    {
        return $this->info->user;
    }

    public function hasExpired()
    {
        global $db, $CFG;

        if ($this->info->limit_date != "0000-00-00" && $this->info->limit_date < date("Y-m-d")) {
            return true;
        }

        if ($this->info->limit_invoices >= 0 && $this->info->limit_invoices - $db->query("SELECT COUNT(*) c FROM invoiceitems WHERE recurring = " . $this->info->ID)->fetch_object()->c <= 0) {
            return true;
        }

        return false;
    }

    public function bill($mail = true, $id = null, $force = false)
    {
        global $db, $CFG, $dfo;
        if ($this->getStatus() != 1 && !$force) {
            return false;
        }

        if ($this->getNext() > date("Y-m-d") && !$force) {
            return false;
        }

        if ($this->hasExpired()) {
            return false;
        }

        $time = $this->info->show_period ? "<br /><br /><i>" . $dfo->format($this->getNext(), false) . " - " . $dfo->format(strtotime("-1 day, +" . $this->info->period, strtotime($this->getNext())), false) . "</i>" : "";

        $item = new InvoiceItem;
        $item->setDescription($this->info->description . $time);
        $item->setAmount($this->info->amount);
        $item->setRecurring($this->info->ID);

        $invoice = new Invoice;

        if ($id !== null) {
            $invoice->load($id);
        } else {
            $invoice->setDate(date("Y-m-d"));
            $invoice->setClient($this->info->user);
            $invoice->setDueDate();
            $invoice->setStatus(0);
        }

        $invoice->addItem($item);
        $invoice->save();
        $id = $invoice->getId();

        $db->query("UPDATE invoice_items_recurring SET last = '" . $this->getNext() . "' WHERE ID = " . $this->info->ID);
        $this->info->last = $this->getNext();

        if (!$force) {
            $this->bill(false, $id);
        }

        if ($mail) {
            $invoice->send();
        }

        return $id;
    }
}
