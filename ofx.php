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
        $tz = intval($tz);
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

        echo $request;
    }
 
    private $_uri = null;
    private $_user_id = null;
    private $_org = null;
    private $_fid = null;
    private $_bank_id = null;
    private $_acct_id = null;
} // END class OFX
