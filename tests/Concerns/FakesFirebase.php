<?php

namespace Tests\Concerns;

use App\Services\Push\FcmService;

trait FakesFirebase
{
    /**
     * Point the app at a throwaway service-account key.
     */
    protected function configureFirebase(): void
    {
        // A throwaway key made on the spot, so no private key ever lives in the repository
        $config = storage_path('framework/testing/openssl-'.uniqid().'.cnf');
        @mkdir(dirname($config), 0777, true);
        file_put_contents($config, "[req]
distinguished_name = dn
[dn]
");
        $options = ['config' => $config, 'private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA];
        openssl_pkey_export(openssl_pkey_new($options), $pem, null, $options);
        @unlink($config);

        $path = storage_path('framework/testing/fcm-'.uniqid().'.json');
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode([
            'project_id' => 'maqam-test', 'client_email' => 'push@maqam-test.iam.gserviceaccount.com', 'private_key' => $pem,
        ]));
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        config(['services.firebase.credentials' => $path]);
        $this->app->forgetInstance(FcmService::class);
    }
}
