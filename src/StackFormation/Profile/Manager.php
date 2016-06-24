<?php

namespace StackFormation\Profile;

class Manager {

    protected $sdk;
    protected $clients = [];
    protected $credentialProvider;

    public function __construct(YamlCredentialProvider $credentialProvider=null)
    {
        $this->credentialProvider = is_null($credentialProvider) ? new YamlCredentialProvider() : $credentialProvider;
    }

    protected function getSdk()
    {
        if (is_null($this->sdk)) {
            $region = getenv('AWS_DEFAULT_REGION');
            if (empty($region)) {
                throw new \Exception('Environment variable AWS_DEFAULT_REGION not set.');
            }
            $this->sdk = new \Aws\Sdk([
                'version' => 'latest',
                'region' => $region,
                'retries' => 20
            ]);
        }
        return $this->sdk;
    }

    /**
     * @param string $client
     * @param string $profile
     * @param array $args
     * @return \Aws\AwsClientInterface
     * @throws \Exception
     */
    public function getClient($client, $profile=null, array $args=[]) {
        $cacheKey = $client .'-'. ($profile ? $profile : '__empty__');
        if (!isset($this->clients[$cacheKey])) {
            if ($profile) {
                $args['credentials'] = $this->credentialProvider->getCredentialsForProfile($profile);
            }
            $this->clients[$cacheKey] = $this->getSdk()->createClient($client, $args);
        }
        return $this->clients[$cacheKey];
    }

    public function listAllProfiles()
    {
        return $this->credentialProvider->listAllProfiles();
    }

    public function writeProfileToDotEnv($profile, $file='.env') {
        $tmp = [];
        foreach ($this->credentialProvider->getEnvVarsForProfile($profile) as $var => $value) {
            $tmp[] = "$var=$value";
        }
        $res = file_put_contents($file, implode("\n", $tmp));
        if ($res === false) {
            throw new \Exception('Error while writing file .env');
        }
        return $file;
    }

}