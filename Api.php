<?php
namespace Payum\Be2Bill;

use Http\Message\MessageFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;
use Payum\Be2Bill\Request\Callback;
use Payum\Be2Bill\Request\ReturnFromPaymentSystem;

class Api
{
    const VERSION = '3.0';

    const EXECCODE_SUCCESSFUL = '0000';

    const EXECCODE_3DSECURE_IDENTIFICATION_REQUIRED = '0001';

    const EXECCODE_SDD_NEED_PROCESS_IBAN = '0002';

    const EXECCODE_SDD_PENDING_PROCESSING = '0003';

    const EXECCODE_PARAMETER_X_MISSING = '1001';

    const EXECCODE_INVALID_PARAMETER_X = '1002';

    const EXECCODE_HASH_ERROR = '1003';

    const EXECCODE_UNSUPPORTED_PROTOCOL = '1004';

    const EXECCODE_ALIAS_NOT_FOUND = '2001';

    const EXECCODE_UNSUCCESSFUL_REFERENCE_TRANSACTION = '2002';

    const EXECCODE_NON_REFUNDABLE_REFERENCE_TRANSACTION = '2003';

    const EXECCODE_REFERENCE_TRANSACTION_NOT_FOUND = '2004';


    const EXECCODE_NOT_ABLE_TO_CAPTURE_THE_REFERENCE_AUTHORIZATION = '2005';

    const EXECCODE_UNFINISHED_REFERENCE_TRANSACTION = '2006';

    const EXECCODE_INVALID_CAPTURE_AMOUNT = '2007';

    const EXECCODE_ACCOUNT_DEACTIVATED = '3001';

    const EXECCODE_UNAUTHORIZED_SERVER_IP_ADDRESS = '3002';

    const EXECCODE_UNAUTHORIZED_TRANSACTION = '3003';

    const EXECCODE_TRANSACTION_REFUSED_BY_THE_BANK = '4001';

    const EXECCODE_INSUFFICIENT_FUNDS = '4002';

    const EXECCODE_CARD_REFUSED_BY_THE_BANK = '4003';

    const EXECCODE_ABORTED_TRANSACTION = '4004';

    const EXECCODE_SUSPECTED_FRAUD = '4005';

    const EXECCODE_CARD_LOST = '4006';

    const EXECCODE_STOLEN_CARD = '4007';

    const EXECCODE_3DSECURE_AUTHENTICATION_FAILED = '4008';

    const EXECCODE_EXPIRED_3DSECURE_AUTHENTICATION = '4009';

    const EXECCODE_INTERNAL_ERROR = '5001';

    const EXECCODE_BANK_ERROR = '5002';

    const EXECCODE_UNDERGOING_MAINTENANCE = '5003';

    const EXECCODE_TIME_OUT = '5004';

    /**
     * The "payment" function is the basic function that allows collecting from a cardholder.
     * This operation collects money directly.
     */
    const OPERATION_PAYMENT = 'payment';

    /**
     * The "authorization" function allows "freezing" temporarily the funds in a cardholder's bank
     * account for 7 days. This application does not debit it.
     * This type of operation is mainly used in the world of physical goods ("retail") when the merchant
     * decides to debit his customer at merchandise shipping time.
     */
    const OPERATION_AUTHORIZATION = 'authorization';

    /**
     * The "capture" function allows collecting funds from a cardholder after an authorization
     * ("authorization" function). This capture can take place within 7 days after the authorization.
     */
    const OPERATION_CAPTURE = 'capture';

    /**
     * This dual function is directly managed by the system:
     * - Refund: Consists of returning the already collected funds to a cardholder
     * - Cancellation: Consists of not sending a payment transaction as compensation
     */
    const OPERATION_REFUND = 'refund';

    /**
     * The "credit" function allows sending funds to a cardholder.
     */
    const OPERATION_CREDIT = 'credit';

    const CLIENT_ACCEPT_HEADER = 'application/json';

    const SUSPICIOUSACCOUNTACTIVITY_YES = 'yes';

    const SUSPICIOUSACCOUNTACTIVITY_NO = 'no';

    const CLIENTAUTHMETHOD_CREDENTIALS = 'credentials';
    const CLIENTAUTHMETHOD_GUEST = 'guest';
    const CLIENTAUTHMETHOD_FEDERATED = 'federated';
    const CLIENTAUTHMETHOD_ISSUER = 'issuer';
    const CLIENTAUTHMETHOD_THIRDPARTY = 'thirdparty';
    const CLIENTAUTHMETHOD_FIDO = 'fido';

    const THREEDSECUREPREFERENCE_NO_PREF = 'nopref';

    const SHIPTOADDRESSTYPE_BILLING = 'billing';
    const SHIPTOADDRESSTYPE_VERIFIED = 'verified';
    const SHIPTOADDRESSTYPE_NEW = 'new';
    const SHIPTOADDRESSTYPE_STORE_PICKUP = 'storepickup';
    const SHIPTOADDRESSTYPE_EDELIVERY = 'edelivery';
    const SHIPTOADDRESSTYPE_TRAVELPICKUP = 'travelpickup';
    const SHIPTOADDRESSTYPE_OTHER = 'other';

    const REORDERINGITEM_YES = 'yes';
    const REORDERINGITEM_NO = 'no';

    const ALIASMODE_ONECLICK  = 'oneclick';
    const ALIASMODE_SUBSCRIPTION  = 'subscription';

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var MessageFactory
     */
    protected $messageFactory;

    /**
     * @var array
     */
    protected $options = array(
        'identifier' => null,
        'password' => null,
        'sandbox' => null,
    );

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     * @param MessageFactory      $messageFactory
     *
     * @throws \Payum\Core\Exception\InvalidArgumentException if an option is invalid
     */
    public function __construct(array $options, HttpClientInterface $client, MessageFactory $messageFactory)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty(array(
            'identifier',
            'password',
        ));

        if (false == is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }

        $this->options = $options;
        $this->client = $client;
        $this->messageFactory = $messageFactory;
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function payment(array $params)
    {
        $params['OPERATIONTYPE'] = static::OPERATION_PAYMENT;

        $this->addGlobalParams($params);

        return $this->doRequest([
            'method' => 'payment',
            'params' => $params
        ]);
    }

    /**
     * @param array $params
     *
     * @param string $cardType
     * @return array
     */
    public function hostedFieldsPayment(array $params, $cardType)
    {
        $this->addCommonParams($params);
        $params['IDENTIFIER'] = $this->resolveIdentifier($cardType);
        $params['3DSECUREPREFERENCE'] = self::THREEDSECUREPREFERENCE_NO_PREF;

        $params['HASH'] = $this->calculateHashForSecret($params, $this->resolveHostedFieldsSecret($cardType));

        return $this->doRequest([
            'method' => 'payment',
            'params' => $params
        ]);
    }

    /**
     * @param array $params
     * @return array
     */
    public function sddPayment(array $params)
    {
        $supportedParams = [
            'ORDERID' => null,
            'AMOUNT' => null,
            'CLIENTIDENT' => null,
            'CLIENTEMAIL' => null,
            'CLIENTGENDER' => null,
            'BILLINGFIRSTNAME' => null,
            'BILLINGLASTNAME' => null,
            'BILLINGADDRESS' => null,
            'BILLINGCITY' => null,
            'BILLINGCOUNTRY' => null,
            'BILLINGMOBILEPHONE' => null,
            'BILLINGPOSTALCODE' => null,
            'CLIENTUSERAGENT' => null,
            'CLIENTIP' => null,
            'DESCRIPTION' => null,
        ];

        $oldParams = $params;
        $params = array_filter(array_replace(
            $supportedParams,
            array_intersect_key($params, $supportedParams)
        ));

        $keys = [
            'LANGUAGE', 'CLIENTJAVAENABLED', 'CLIENTSCREENCOLORDEPTH',
            'CLIENTSCREENWIDTH', 'CLIENTSCREENHEIGHT', 'TIMEZONE',
        ];

        foreach ($keys as $key) {
            if (isset($oldParams[$key])) {
                $params[$key] = $oldParams[$key];
            }
        }

        $this->addCommonParams($params);
        $params['IDENTIFIER'] = $this->options['sdd_identifier'];
        $params['HASH'] = $this->calculateHashForSecret($params, $this->options['sdd_secret']);

        return $this->doRequest([
            'method' => 'payment',
            'params' => $params
        ]);
    }

    /**
     * Verify if the hash of the given parameter is correct
     *
     * @param array $params
     *
     * @return bool
     */
    public function verifyHash(array $params)
    {
        if (empty($params['HASH'])) {
            return false;
        }

        $hash = $params['HASH'];
        unset($params['HASH']);

        return $hash === $this->calculateHash($params);
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function doRequest(array $fields)
    {
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        );

        $request = $this->messageFactory->createRequest('POST', $this->getApiEndpoint(), $headers, http_build_query($fields));
        $response = $this->client->send($request);

        if (false == ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        $result = json_decode($response->getBody()->getContents());
        if (null === $result) {
            throw new LogicException("Response content is not valid json: \n\n{$response->getBody()->getContents()}");
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getOffsiteUrl()
    {
        return $this->options['sandbox'] ?
            'https://secure-test.be2bill.com/front/form/process.php' :
            'https://secure-magenta1.be2bill.com/front/form/process.php'
        ;
    }

    /**
     * @return string
     */
    public function getHostedFieldsJsLibUrl()
    {
        return $this->options['sandbox'] ?
            'https://js.sandbox.be2bill.com/hosted-fields/v1/hosted-fields.min.js' :
            'https://js.be2bill.com/hosted-fields/v1/hosted-fields.min.js'
        ;
    }

    /**
     * @return string
     */
    public function getBrandDetectorJsLibUrl()
    {
        return $this->options['sandbox'] ?
            'https://js.sandbox.be2bill.com/brand-detector/v1/brand-detector.min.js' :
            'https://js.be2bill.com/brand-detector/v1/brand-detector.min.js'
        ;
    }

    /**
     * @param  array $params
     *
     * @return array
     */
    public function prepareOffsitePayment(array $params)
    {
        $supportedParams = array(
            'CLIENTIDENT' => null,
            'DESCRIPTION' => null,
            'ORDERID' => null,
            'AMOUNT' => null,
            'CARDTYPE' => null,
            'CLIENTEMAIL' => null,
            'CARDFULLNAME' => null,
            'LANGUAGE' => null,
            'EXTRADATA' => null,
            'CLIENTDOB' => null,
            'CLIENTADDRESS' => null,
            'CREATEALIAS' => null,
            '3DSECURE' => null,
            '3DSECUREDISPLAYMODE' => null,
            'USETEMPLATE' => null,
            'HIDECLIENTEMAIL' => null,
            'HIDEFULLNAME' => null,
        );

        $params = array_filter(array_replace(
            $supportedParams,
            array_intersect_key($params, $supportedParams)
        ));

        $params['OPERATIONTYPE'] = static::OPERATION_PAYMENT;

        $this->addGlobalParams($params);

        return $params;
    }

    /**
     * @param  array $params
     */
    protected function addCommonParams(array &$params)
    {
        $params['OPERATIONTYPE'] = static::OPERATION_PAYMENT;
        $params['VERSION'] = self::VERSION;
        $params['CLIENTACCEPTHEADER'] = self::CLIENT_ACCEPT_HEADER;
    }

    /**
     * @param  array $params
     */
    protected function addGlobalParams(array &$params)
    {
        $params['VERSION'] = self::VERSION;
        $params['IDENTIFIER'] = $this->options['identifier'];
        $params['HASH'] = $this->calculateHash($params);
    }
    /**
     * @return string
     */
    protected function getApiEndpoint()
    {
        return $this->options['sandbox'] ?
            'https://secure-test.be2bill.com/front/service/rest/process' :
            'https://secure-magenta1.be2bill.com/front/service/rest/process'
        ;
    }

    /**
     * @param array $params
     *
     * @return string
     */
    public function calculateHash(array $params)
    {
        return $this->calculateHashForSecret($params, $this->options['password']);
    }

    /**
     * @param array $params
     *
     * @param $secret
     * @return string
     */
    public function calculateHashForSecret(array $params, $secret)
    {
        #Alpha sort
        ksort($params);

        $clearString = $secret;
        foreach ($params as $key => $value) {
            $clearString .= $key.'='.$value.$secret;
        }

        return hash('sha256', $clearString);
    }

    /**
     * @param array $requestData
     * @return Callback
     */
    public function parseCallbackRequest(array $requestData)
    {
        $hash = $requestData['HASH'];
        $orderId = $requestData['ORDERID'];
        $transactionId = $requestData['TRANSACTIONID'];
        $execCode = $requestData['EXECCODE'];
        $message = $requestData['MESSAGE'];

        if (!$hash || !$orderId || !$transactionId || !$execCode) {
            throw new \InvalidArgumentException('Missed required Request data field');
        }

        unset($requestData['HASH']);
        $secret = $this->resolveSecretByIdentifier($requestData['IDENTIFIER']);

        if ($this->calculateHashForSecret($requestData, $secret) !== $hash) {
            throw new \InvalidArgumentException('Corrupted Data');
        }

        return new Callback($execCode, $orderId, $transactionId, $message);
    }

    /**
     * @param array $requestData
     * @return ReturnFromPaymentSystem
     */
    public function parseReturnFromPaymentSystemRequest(array $requestData)
    {
        $hash = $requestData['HASH'];
        $orderId = $requestData['ORDERID'];
        $transactionId = $requestData['TRANSACTIONID'];
        $execCode = $requestData['EXECCODE'];
        $message = $requestData['MESSAGE'];

        if (!$hash || !$orderId || !$transactionId || !$execCode) {
            throw new \InvalidArgumentException('Missed required Request data field');
        }

        unset($requestData['HASH']);
        $secret = $this->resolveSecretByIdentifier($requestData['IDENTIFIER']);

        if ($this->calculateHashForSecret($requestData, $secret) !== $hash) {
            throw new \InvalidArgumentException('Corrupted Data');
        }

        return new ReturnFromPaymentSystem(
            $execCode,
            $orderId,
            $transactionId,
            $message,
            isset($requestData['3DSECUREAUTHENTICATIONSTATUS']) ? $requestData['3DSECUREAUTHENTICATIONSTATUS'] : '',
            isset($requestData['3DSECURESIGNATURESTATUS']) ? $requestData['3DSECURESIGNATURESTATUS'] : '',
            isset($requestData['3DSGLOBALSTATUS']) ? $requestData['3DSGLOBALSTATUS'] : '',
            isset($requestData['CARD3DSECUREENROLLED']) ? $requestData['CARD3DSECUREENROLLED'] : '',
            isset($requestData['ALIAS']) ? $requestData['ALIAS'] : ''
        );
    }

    /**
     * @return array
     */
    public function getObtainJsTokenCredentials()
    {
        return [
            'id' => $this->options['apikeyid'],
            'value' => $this->options['password'],
        ];
    }

    /**
     * @return mixed
     */
    public function getIsForce3dSecure()
    {
        return $this->options['force_3d_secure'];
    }

    /**
     * @param string $cardType
     * @return string
     */
    private function resolveIdentifier($cardType)
    {
        $cardType = strtolower($cardType);

        if ($cardType === 'american_express') {
            return $this->options['amex_identifier'];
        }

        return $this->options['identifier'];
    }

    /**
     * @param string $cardType
     * @return string
     */
    private function resolveHostedFieldsSecret($cardType)
    {
        $cardType = strtolower($cardType);

        if ($cardType === 'american_express') {
            return $this->options['amex_secret'];
        }

        return $this->options['secret'];
    }

    /**
     * @param string $identifier
     * @return string
     */
    private function resolveSecretByIdentifier($identifier)
    {
        if ($identifier === $this->options['identifier']) {
            return $this->options['secret'];
        }

        if ($identifier === $this->options['amex_identifier']) {
            return $this->options['amex_secret'];
        }

        if ($identifier === $this->options['sdd_identifier']) {
            return $this->options['sdd_secret'];
        }

        return $this->options['secret'];
    }
}
