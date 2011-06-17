<?php

namespace Seventymph\OFX;

/**
 * Interact with OFX servers.
 *
 **/
class OFX
{
    const REQUEST = <<<'OFX'
OFXHEADER:100
DATA:OFXSGML
VERSION:102
SECURITY:NONE
ENCODING:USASCII
CHARSET:1252
COMPRESSION:NONE
OLDFILEUID:NONE
NEWFILEUID:NONE


<OFX>
    <SIGNONMSGSRQV1>
        <SONRQ>
            <DTCLIENT>${TIMESTAMP}
            <USERID>${USER_ID}
            <USERPASS>${PASSWORD}
            <LANGUAGE>ENG
            <FI>
            <ORG>${ORG}
            <FID>${FID}
            </FI>
            <APPID>QWIN
            <APPVER>0900
        </SONRQ>
    </SIGNONMSGSRQV1>
    <BANKMSGSRQV1>
        <STMTTRNRQ>
            <TRNUID>23382938
            <STMTRQ>
                <BANKACCTFROM>
                    <BANKID>${BANK_ID}
                    <ACCTID>${ACCT_ID}
                    <ACCTTYPE>CHECKING
                </BANKACCTFROM>
                <INCTRAN>
                    <INCLUDE>Y
                </INCTRAN>
            </STMTRQ>
        </STMTTRNRQ>
    </BANKMSGSRQV1>
</OFX>
OFX;

    /**
     * Constructor.
     *
     * @param array $config Valid keys (all required):
     * -uri string
     * -user_id string
     * -password string
     * -org string
     * -fid string
     * -bank_id string
     * -acct_id string
     * @return void
     **/
    public function __construct($config) {
        $uri = null;
        $user_id = null;
        $password = null;
        $org = null;
        $fid = null;
        $bank_id = null;
        $acct_id = null;
        extract($config);

        if (empty($uri) ||empty($user_id) || empty($password) || empty($org)
            || empty($fid) || empty($bank_id) || empty($acct_id))
        {
            throw new Exception("Did not supply all parameters.");
        }

        $this->_uri = $uri;
        $this->_user_id = $user_id;
        $this->_password = $password;
        $this->_org = $org;
        $this->_fid = $fid;
        $this->_bank_id = $bank_id;
        $this->_acct_id = $acct_id;
    }

    /**
     * Fetch transations.
     *
     * @return array Transactions.
     **/
    public function fetch() {
        $request = OFX::REQUEST;

        $tz = strftime("%z", time());
        $tz = intval($tz) / 100;  // Have to hack off the "00" at the end.
        if ($tz >= 0) {
            $tz = "+$tz";
        }
        $now = strftime("%Y%m%d%H%M%S.000[$tz:%Z]", time());

        $request = str_replace('${TIMESTAMP}', $now, $request);
        $request = str_replace('${USER_ID}', $this->_user_id, $request);
        $request = str_replace('${PASSWORD}', $this->_password, $request);
        $request = str_replace('${ORG}', $this->_org, $request);
        $request = str_replace('${FID}', $this->_fid, $request);
        $request = str_replace('${BANK_ID}', $this->_bank_id, $request);
        $request = str_replace('${ACCT_ID}', $this->_acct_id, $request);

        // Perform the HTTP request.
        $curl = curl_init($this->_uri);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/x-ofx",
            "Accept: */*, application/x-ofx",
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
        $result = curl_exec($curl);
        curl_close($curl);

        // Parse transactions.
        $txns = array();

        $lines = explode("\n", $result);
        $txn = null;
        foreach ($lines as $line) {
            // New transaction.
            if ($line == "<STMTTRN>") {
                $txn = new \stdClass();

                // Init data attributes, so nothing is undefined.
                $txn->type = null;
                $txn->posted_time = null;
                $txn->user_time = null;
                $txn->amount = null;
                $txn->who = null;
                $txn->memo = null;
            }
            elseif (strstr($line, "<TRNTYPE>") !== false) {
                $txn->type = substr($line, 9);
            }
            elseif (strstr($line, "<DTPOSTED>") !== false) {
                $txn->posted_time = strtotime(substr($line, 10));
            }
            elseif (strstr($line, "<DTUSER>") !== false) {
                $txn->user_time = strtotime(substr($line, 8));
            }
            elseif (strstr($line, "<TRNAMT>") !== false) {
                $txn->amount = floatval(substr($line, 8));
            }
            elseif (strstr($line, "<NAME>") !== false) {
                $txn->who = substr($line, 6);
            }
            elseif (strstr($line, "<MEMO>") !== false) {
                $txn->memo = substr($line, 6);
            }
            // End of transaction.
            elseif ($line == "</STMTTRN>") {
                $txns []= $txn;
            }
        }

        // Return.
        return $txns;
    }
 
    private $_uri = null;
    private $_user_id = null;
    private $_org = null;
    private $_fid = null;
    private $_bank_id = null;
    private $_acct_id = null;
} // END class OFX
