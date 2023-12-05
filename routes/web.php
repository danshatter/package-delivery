<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function() use ($router) {
    return $router->app->version();
});

$router->get('/all-users', function() {
    return \App\Models\User::all();
}); // For Development

$router->get('/test', ['uses' => 'TestController@index']); // For Development

$router->group([
    'prefix' => '/api'
], function() use ($router) {

    $router->get('/vehicles', ['as' => 'vehicles.index', 'uses' => 'VehicleController@index']);
    $router->get('/vehicles/{vehicleId}', ['as' => 'vehicles.show', 'uses' => 'VehicleController@show']);
    $router->get('/vehicles/{vehicleId}/brands', ['as' => 'vehicles.show-brands', 'uses' => 'VehicleController@showBrands']);

    $router->get('/vehicle-brands', ['as' => 'vehicle-brands.index', 'uses' => 'VehicleBrandController@index']);
    $router->get('/vehicle-brands/{vehicleBrandId}', ['as' => 'vehicle-brands.show', 'uses' => 'VehicleBrandController@show']);
    $router->get('/vehicle-brands/{vehicleBrandId}/models', ['as' => 'vehicle-brands.show-models', 'uses' => 'VehicleBrandController@showModels']);

    $router->get('/vehicle-models', ['as' => 'vehicle-models.index', 'uses' => 'VehicleModelController@index']);
    $router->get('/vehicle-models/{vehicleModelId}', ['as' => 'vehicle-models.show', 'uses' => 'VehicleModelController@show']);

    $router->group([
        'namespace' => 'Authentication'
    ], function() use ($router) {

        $router->post('/login', ['as' => 'login', 'uses' => 'MainController@login']);
        $router->post('/login/google', ['as' => 'login.google', 'uses' => 'MainController@loginWithGoogle']);
        $router->post('/login/facebook', ['as' => 'login.facebook', 'uses' => 'MainController@loginWithFacebook']);

        $router->post('/forgot-password', ['as' => 'forgot-password', 'uses' => 'MainController@forgotPassword']);

        $router->put('/reset-password', ['as' => 'reset-password', 'uses' => 'MainController@resetPassword']);

        $router->post('/otp/resend', ['as' => 'resend-otp', 'uses' => 'MainController@resendOtp']);

        $router->put('/users/verify', ['as' => 'user-verification', 'uses' => 'MainController@userVerify']);

        /**
         * Customer registration
         */
        $router->group([
            'prefix' => '/customers',
            'as' => 'customers'
        ], function() use ($router) {

            $router->post('/register', ['as' => 'registration', 'uses' => 'MainController@customerRegistration']);
        });


        /**
         * Driver registration
         */
        $router->group([
            'prefix' => '/drivers',
            'as' => 'drivers'
        ], function() use ($router) {

            $router->post('/register', ['as' => 'registration', 'uses' => 'MainController@driverRegistration']);

            $router->group([
                'middleware' => ['auth', 'valid_token', 'verified', 'active']
            ], function() use ($router) {

                $router->post('/register/ride', ['as' => 'registration.ride', 'uses' => 'MainController@driverRideRegistration']);
            });
        });


        /**
         * Admin registration (For Development Purposes)
         */
        $router->group([
            'prefix' => '/admin',
            'as' => 'admin'
        ], function() use ($router) {

            $router->post('/register', ['as' => 'registration', 'uses' => 'MainController@adminRegistration']); // For Development

            $router->post('/login', ['as' => 'login', 'uses' => 'MainController@adminLogin']);
        });


        /**
         * Authenticated routes that changes user state
         */
        $router->group([
            'middleware' => ['auth', 'valid_token']
        ], function() use ($router) {

            $router->post('/logout', ['as' => 'logout', 'uses' => 'MainController@logout']);

            $router->group([
                'middleware' => ['verified', 'active']
            ], function() use ($router) {

                $router->put('/change-password', ['as' => 'change-password', 'uses' => 'MainController@changePassword']);
            });
        });
    
    });


    $router->group([
        'middleware' => ['auth', 'valid_token', 'verified', 'active']
    ], function() use ($router) {
        
        $router->get('/users/profile', ['as' => 'profile.show', 'uses' => 'UserController@show']);
        $router->post('/users/profile-image', ['as' => 'profile.image', 'uses' => 'UserController@profileImage']);

        $router->get('/notifications', ['as' => 'notifications.index', 'uses' => 'NotificationController@index']);
        $router->get('/notifications/unread', ['as' => 'notifications.unread', 'uses' => 'NotificationController@unread']);
        $router->get('/notifications/undelivered', ['as' => 'notifications.undelivered', 'uses' => 'NotificationController@undelivered']);
        $router->get('/notifications/{notificationId}', ['as' => 'notification.show', 'uses' => 'NotificationController@show']);
        $router->put('/notifications/{notificationId}/read', ['as' => 'notifications.read', 'uses' => 'NotificationController@read']);
        $router->put('/notifications/{notificationId}/delivered', ['as' => 'notifications.delivered', 'uses' => 'NotificationController@delivered']);

        $router->get('/messages', ['as' => 'messages.index', 'uses' => 'MessageController@index']);
        $router->post('/messages', ['as' => 'messages.store', 'uses' => 'MessageController@store']);
        $router->get('/messages/unread', ['as' => 'messages.unread', 'uses' => 'MessageController@unread']);
        $router->get('/messages/undelivered', ['as' => 'messages.undelivered', 'uses' => 'MessageController@undelivered']);
        $router->get('/messages/{messageId}', ['as' => 'message.show', 'uses' => 'MessageController@show']);
        $router->put('/messages/{messageId}/read', ['as' => 'messages.read', 'uses' => 'MessageController@read']);
        $router->put('/messages/{messageId}/delivered', ['as' => 'messages.delivered', 'uses' => 'MessageController@delivered']);

        $router->get('/transactions', ['as' => 'transactions', 'uses' => 'TransactionController@index']);
        $router->get('/transactions/{transactionId}', ['as' => 'transactions.show', 'uses' => 'TransactionController@show']);

        $router->get('/categories', ['as' => 'categories.index', 'uses' => 'CategoryController@index']);
        $router->get('/categories/{categoryId}', ['as' => 'categories.show', 'uses' => 'CategoryController@show']);

        /**
         * All routes concerning google services
         */
        $router->group([
            'namespace' => 'Google',
            'as' => 'google'
        ], function() use ($router) {

            $router->get('/geocoding', ['as' => 'geocoding', 'uses' => 'GeocodingController@direct']);
            $router->get('/geocoding/reverse', ['as' => 'geocoding-reverse', 'uses' => 'GeocodingController@reverse']);

            $router->get('/geolocation', ['as' => 'geolocation', 'uses' => 'GeolocationController@show']);

            $router->get('/directions', ['as' => 'direction', 'uses' => 'DirectionController@show']);
        });


        /**
         * Customers exclusive routes after authentication
         */
        $router->group([
            'middleware' => ['customer'],
            'prefix' => '/customers',
            'as' => 'customers'
        ], function() use ($router) {

            $router->put('/profile', ['as' => 'profile', 'uses' => 'UserController@customerUpdate']);

            $router->post('/orders', ['as' => 'orders.store', 'uses' => 'OrderController@store']);
            $router->put('/orders/{orderId}', ['as' => 'orders.update', 'uses' => 'OrderController@update']);

            $router->post('/accounts/credit', ['as' => 'accounts', 'uses' => 'TransactionController@credit']); // For Development

            $router->get('/cards', ['as' => 'cards.index', 'uses' => 'CardController@index']);
            $router->get('/cards/{cardId}', ['as' => 'cards.show', 'uses' => 'CardController@show']);
            $router->delete('/cards/{cardId}', ['as' => 'cards.destroy', 'uses' => 'CardController@destroy']);

            /**
             * Customer functionalities in Customer namespace
             */
            $router->group([
                'namespace' => 'Customer'
            ], function() use ($router) {

                $router->get('/ratings', ['as' => 'ratings.index', 'uses' => 'MainController@ratings']);
                $router->post('/ratings', ['as' => 'ratings.store', 'uses' => 'MainController@storeRating']);
                $router->get('/ratings/{ratingId}', ['as' => 'ratings.show', 'uses' => 'MainController@showRatings']);
                $router->put('/ratings/{ratingId}', ['as' => 'ratings.update', 'uses' => 'MainController@updateRating']);
                $router->delete('/ratings/{ratingId}', ['as' => 'ratings.destroy', 'uses' => 'MainController@destroyRating']);

                $router->get('/orders', ['as' => 'orders.index', 'uses' => 'MainController@orders']);
                $router->get('/orders/estimation', ['as' => 'orders.estimation', 'uses' => 'MainController@orderEstimation']);
                $router->get('/orders/active', ['as' => 'orders.active', 'uses' => 'MainController@active']);
                $router->get('/orders/{orderId}', ['as' => 'orders.show', 'uses' => 'MainController@showOrder']);
                $router->get('/orders/{orderId}/track', ['as' => 'orders.track', 'uses' => 'MainController@trackOrder']);
                $router->get('/orders/{orderId}/arrival-time', ['as' => 'orders.arrival-time', 'uses' => 'MainController@driverArrivalTime']);
                $router->post('/orders/{orderId}/rate', ['as' => 'orders.rate', 'uses' => 'MainController@rateOrder']);
                $router->post('/orders/{orderId}/cancel', ['as' => 'orders.cancel', 'uses' => 'MainController@cancelOrder']);
                $router->get('/orders/drivers/search', ['as' => 'search', 'uses' => 'MainController@searchDrivers']);
                $router->get('/orders/vehicles/search', ['as' => 'search', 'uses' => 'MainController@searchVehicles']);
            });
        });


        /**
         * Drivers exclusive routes after authentication
         */
        $router->group([
            'middleware' => ['driver'],
            'prefix' => '/drivers',
            'as' => 'drivers'
        ], function() use ($router) {

            $router->put('/profile', ['as' => 'profile', 'uses' => 'UserController@driverUpdate']);

            $router->group([
                'middleware' => ['accepted_driver']
            ], function() use ($router) {
                $router->get('/banks', ['as' => 'banks.index', 'uses' => 'AccountController@banks']);
                $router->get('/banks/{bankId}', ['as' => 'banks.show', 'uses' => 'AccountController@bank']);
        
                $router->get('/accounts', ['as' => 'accounts.index', 'uses' => 'AccountController@index']);
                $router->post('/accounts', ['as' => 'accounts.store', 'uses' => 'AccountController@store']);
                $router->get('/accounts/verify', ['as' => 'accounts.verify', 'uses' => 'AccountController@verify']);
                $router->get('/accounts/{accountId}', ['as' => 'accounts.show', 'uses' => 'AccountController@show']);
                $router->delete('/accounts/{accountId}', ['as' => 'accounts.destroy', 'uses' => 'AccountController@destroy']);
            });

            /**
             * Driver functionalites in driver namespace
             */
            $router->group([
                'namespace' => 'Driver'
            ], function() use ($router) {

                $router->get('/status', ['as' => 'status', 'uses' => 'MainController@status']);

                $router->get('/rides/status', ['as' => 'rides.status', 'uses' => 'MainController@rideStatus']);

                /**
                 * The Core driver tasks after acceptance
                 */
                $router->group([
                    'middleware' => ['accepted_driver']
                ], function() use ($router) {

                    $router->get('/jobs', ['as' => 'jobs.index', 'uses' => 'MainController@jobs']);
                    $router->get('/jobs/{jobId}', ['as' => 'jobs.show', 'uses' => 'MainController@showJob']);
                    $router->put('/jobs/{jobId}/accept', ['as' => 'jobs.accept', 'uses' => 'MainController@acceptJob']);
                    $router->put('/jobs/{jobId}/reject', ['as' => 'jobs.reject', 'uses' => 'MainController@rejectJob']);
                    $router->put('/jobs/{jobId}/en-route', ['as' => 'jobs.en-route', 'uses' => 'MainController@jobEnRoute']);
                    $router->put('/jobs/{jobId}/completed', ['as' => 'jobs.completed', 'uses' => 'MainController@jobCompleted']);

                    $router->put('/locations', ['as' => 'locations', 'uses' => 'MainController@updateCurrentLocation']);
                    $router->get('/online', ['as' => 'online.check', 'uses' => 'MainController@online']);
                    $router->put('/online', ['as' => 'online.activate', 'uses' => 'MainController@goOnline']);
                    $router->put('/offline', ['as' => 'offline.activate', 'uses' => 'MainController@goOffline']);

                    $router->get('/ratings', ['as' => 'ratings', 'uses' => 'MainController@ratings']);
                    $router->get('/ratings/{ratingId}', ['as' => 'ratings', 'uses' => 'MainController@showRating']);

                    $router->post('/rides', ['as' => 'rides', 'uses' => 'MainController@rides']);

                    $router->get('/earnings/week', ['as' => 'earnings.week', 'uses' => 'MainController@weekEarnings']);

                    $router->get('/withdrawals', ['as' => 'withdrawals', 'uses' => 'MainController@withdrawals']);
                    $router->post('/withdrawals', ['as' => 'withdraw', 'uses' => 'MainController@withdraw']);
                });
            });
        });

        
        /**
         * Administrators exclusive routes after authentication
         */
        $router->group([
            'middleware' => ['administrator'],
            'prefix' => '/admin',
            'as' => 'admin'
        ], function() use ($router) {

            $router->put('/profile', ['as' => 'profile', 'uses' => 'UserController@adminUpdate']);

            $router->post('/categories', ['as' => 'categories.store', 'uses' => 'CategoryController@store']);
            $router->put('/categories/{categoryId}', ['as' => 'categories.update', 'uses' => 'CategoryController@update']);
            $router->delete('/categories/{categoryId}', ['as' => 'categories.destroy', 'uses' => 'CategoryController@destroy']);

            $router->post('/vehicles', ['as' => 'vehicles.store', 'uses' => 'VehicleController@store']);
            $router->post('/vehicles/{vehicleId}', ['as' => 'vehicles.update', 'uses' => 'VehicleController@update']);
            $router->post('/vehicles/{vehicleId}/enable', ['as' => 'vehicles.enable', 'uses' => 'VehicleController@enable']);
            $router->post('/vehicles/{vehicleId}/disable', ['as' => 'vehicles.disable', 'uses' => 'VehicleController@disable']);
            $router->delete('/vehicles/{vehicleId}', ['as' => 'vehicles.destroy', 'uses' => 'VehicleController@destroy']);

            $router->post('/vehicle-brands', ['as' => 'vehicle-brands.store', 'uses' => 'VehicleBrandController@store']);
            $router->put('/vehicle-brands/{vehicleBrandId}', ['as' => 'vehicle-brands.update', 'uses' => 'VehicleBrandController@update']);
            $router->delete('/vehicle-brands/{vehicleBrandId}', ['as' => 'vehicle-brands.destroy', 'uses' => 'VehicleBrandController@destroy']);

            $router->post('/vehicle-models', ['as' => 'vehicle-models.store', 'uses' => 'VehicleModelController@store']);
            $router->put('/vehicle-models/{vehicleModelId}', ['as' => 'vehicle-models.update', 'uses' => 'VehicleModelController@update']);
            $router->delete('/vehicle-models/{vehicleModelId}', ['as' => 'vehicle-models.destory', 'uses' => 'VehicleModelController@destroy']);

            $router->get('/notifications', ['as' => 'notifications.index', 'uses' => 'SuperNotificationController@index']);
            $router->get('/notifications/unread', ['as' => 'notifications.unread', 'uses' => 'SuperNotificationController@unread']);
            $router->get('/notifications/undelivered', ['as' => 'notifications.undelivered', 'uses' => 'SuperNotificationController@undelivered']);
            $router->get('/notifications/{notificationId}', ['as' => 'notification.show', 'uses' => 'SuperNotificationController@show']);
            $router->put('/notifications/{notificationId}/read', ['as' => 'notifications.read', 'uses' => 'SuperNotificationController@read']);
            $router->put('/notifications/{notificationId}/delivered', ['as' => 'notifications.delivered', 'uses' => 'SuperNotificationController@delivered']);

            $router->get('/promo-codes', ['as' => 'promo-codes.index', 'uses' => 'PromoCodeController@index']);
            $router->post('/promo-codes', ['as' => 'promo-codes.store', 'uses' => 'PromoCodeController@store']);
            $router->get('/promo-codes/{promoCodeId}', ['as' => 'promo-codes.show', 'uses' => 'PromoCodeController@show']);
            $router->delete('/promo-codes/{promoCodeId}', ['as' => 'promo-codes.destroy', 'uses' => 'PromoCodeController@destroy']);


            /**
             * Admin functionalities in Admin namespace
             */
            $router->group([
                'namespace' => 'Admin'
            ], function() use ($router) {

                $router->post('/notifications/token/test', ['as' => 'notifications.test', 'uses' => 'MainController@sendTestNotification']); // For Development

                $router->post('/logo', ['as' => 'logo', 'uses' => 'MainController@logo']);

                $router->get('/users/active', ['as' => 'users.active', 'uses' => 'MainController@usersActive']);
                $router->get('/users/blocked', ['as' => 'users.blocked', 'uses' => 'MainController@usersBlocked']);
                $router->get('/users/{userId}', ['as' => 'user', 'uses' => 'MainController@user']);
                $router->put('/users/{userId}/activate', ['as' => 'users.activate', 'uses' => 'MainController@userActivate']);
                $router->put('/users/{userId}/block', ['as' => 'users.block', 'uses' => 'MainController@userBlock']);
                $router->post('/users/{userId}/notify', ['as' => 'users.notify', 'uses' => 'MainController@userNotify']);
                $router->post('/users/{userId}/sms', ['as' => 'users.sms', 'uses' => 'MainController@userSms']);
                $router->post('/users/{userId}/mail', ['as' => 'users.mail', 'uses' => 'MainController@userMail']);

                $router->get('/customers', ['as' => 'customers', 'uses' => 'MainController@customers']);
                $router->get('/customers/total', ['as' => 'customers.total', 'uses' => 'MainController@customersTotal']);
                $router->get('/customers/month-leaders', ['as' => 'customers.month-leaders', 'uses' => 'MainController@customerMonthLeaders']);
                $router->get('/customers/all-time-leaders', ['as' => 'customers.all-time-leaders', 'uses' => 'MainController@customerAllTimeLeaders']);
                $router->get('/customers/registered', ['as' => 'customers.registered', 'uses' => 'MainController@customersRegistered']);
                $router->get('/customers/{customerId}', ['as' => 'customer', 'uses' => 'MainController@customer']);
                $router->get('/customers/{customerId}/orders', ['as' => 'customers.orders', 'uses' => 'MainController@customerOrders']);
                $router->get('/customers/{customerId}/orders/{orderId}', ['as' => 'customers.order', 'uses' => 'MainController@customerOrder']);
                $router->get('/customers/{customerId}/transactions', ['as' => 'customers.transactions', 'uses' => 'MainController@customerTransactions']);
                $router->get('/customers/{customerId}/transactions/{transactionId}', ['as' => 'customers.transaction', 'uses' => 'MainController@customerTransaction']);

                $router->get('/drivers', ['as' => 'drivers', 'uses' => 'MainController@drivers']);
                $router->get('/drivers/total', ['as' => 'drivers.total', 'uses' => 'MainController@driversTotal']);
                $router->get('/drivers/online', ['drivers.online', 'uses' => 'MainController@onlineDrivers']);
                $router->get('/drivers/offline', ['as' => 'drivers.offline', 'uses' => 'MainController@offlineDrivers']);
                $router->get('/drivers/approved', ['as' => 'drivers.approved', 'uses' => 'MainController@approvedDrivers']);
                $router->get('/drivers/unapproved', ['as' => 'drivers.unapproved', 'uses' => 'MainController@unapprovedDrivers']);
                $router->get('/drivers/rejected', ['as' => 'drivers.rejected', 'uses' => 'MainController@rejectedDrivers']);
                $router->get('/drivers/month-leaders', ['as' => 'drivers.month-leaders', 'uses' => 'MainController@driverMonthLeaders']);
                $router->get('/drivers/all-time-leaders', ['as' => 'drivers.all-time-leaders', 'uses' => 'MainController@driverAllTimeLeaders']);
                $router->get('/drivers/registered', ['as' => 'drivers.registered', 'uses' => 'MainController@driversRegistered']);
                $router->get('/drivers/{driverId}', ['as' => 'driver', 'uses' => 'MainController@driver']);
                $router->put('/drivers/{driverId}/approve', ['as' => 'drivers.approve', 'uses' => 'MainController@approveDriver']);
                $router->put('/drivers/{driverId}/reject', ['as' => 'drivers.reject', 'uses' => 'MainController@rejectDriver']);
                $router->put('/drivers/{driverId}/rides/approve', ['as' => 'rides.approve', 'uses'=> 'MainController@approveRide']);
                $router->put('/drivers/{driverId}/rides/reject', ['as' => 'rides.reject', 'uses' => 'MainController@rejectRide']);
                $router->get('/drivers/{driverId}/track', ['as' => 'drivers.track', 'uses' => 'MainController@trackDriver']);
                $router->get('/drivers/{driverId}/jobs', ['as' => 'drivers.jobs', 'uses' => 'MainController@driverJobs']);
                $router->get('/drivers/{driverId}/jobs/{jobId}', ['as' => 'drivers.job', 'uses' => 'MainController@driverJob']);
                $router->get('/drivers/{driverId}/transactions', ['as' => 'drivers.transactions', 'uses' => 'MainController@driverTransactions']);
                $router->get('/drivers/{driverId}/transactions/{transactionId}', ['as' => 'drivers.transaction', 'uses' => 'MainController@driverTransaction']);
                $router->get('/drivers/{driverId}/ratings', ['as' => 'drivers.rating', 'uses' => 'MainController@driverRatings']);
                $router->get('/drivers/{driverId}/ratings/{ratingId}', ['as' => 'drivers.rating', 'uses' => 'MainController@driverRating']);
                $router->post('/drivers/{driverId}/license/verify', ['as' => 'drivers.license-verification', 'uses' => 'MainController@verifyDriverLicense']);
                
                $router->get('/orders', ['as' => 'orders', 'uses' => 'MainController@orders']);
                $router->get('/orders/total', ['as' => 'orders.total', 'uses' => 'MainController@ordersTotal']);
                $router->get('/orders/today', ['as' => 'orders.today', 'uses' => 'MainController@ordersToday']);
                $router->get('/orders/pending', ['as' => 'orders.pending', 'uses' => 'MainController@ordersPending']);
                $router->get('/orders/pending/monthly', ['as' => 'orders.pending-monthly', 'uses' => 'MainController@ordersPendingMonthly']);
                $router->get('/orders/accepted', ['as' => 'orders.accepted', 'uses' => 'MainController@ordersAccepted']);
                $router->get('/orders/rejected', ['as' => 'orders.rejected', 'uses' => 'MainController@ordersRejected']);
                $router->get('/orders/rejected/monthly', ['as' => 'orders.rejected-monthly', 'uses' => 'MainController@ordersRejectedMonthly']);
                $router->get('/orders/en-route', ['as' => 'orders.en-route', 'uses' => 'MainController@ordersEnRoute']);
                $router->get('/orders/completed', ['as' => 'orders.completed', 'uses' => 'MainController@ordersCompleted']);
                $router->get('/orders/completed/monthly', ['as' => 'orders.completed-monthly', 'uses' => 'MainController@ordersCompletedMonthly']);
                $router->get('/orders/canceled', ['as' => 'orders.canceled', 'uses' => 'MainController@ordersCanceled']);
                $router->get('/orders/canceled/monthly', ['as' => 'orders.canceled-monthly', 'uses' => 'MainController@ordersCanceledMonthly']);
                $router->get('/orders/{orderId}', ['as' => 'order', 'uses' => 'MainController@order']);
                $router->get('/orders/{orderId}/track', ['as' => 'orders.track', 'uses' => 'MainController@trackOrder']);

                $router->get('/transactions', ['as' => 'transactions.index', 'uses' => 'MainController@transactions']);
                $router->get('/transactions/{transactionId}', ['as' => 'transactions.show', 'uses' => 'MainController@transaction']);
                $router->post('/transactions/{transactionId}/withdrawals/confirm', ['as' => 'transactions.confirm-withdrawal', 'uses' => 'MainController@confirmWithdrawal']);
                $router->post('/transactions/{transactionId}/withdrawals/reject', ['as' => 'transactions.reject-withdrawal', 'uses' => 'MainController@rejectWithdrawal']);
                
                $router->post('/reports/monthly', ['as' => 'reports.monthly', 'uses' => 'MainController@monthlyReport']);
                $router->post('/reports/weekly', ['as' => 'reports.weekly', 'uses' => 'MainController@weeklyReport']);
                $router->post('/reports/daily', ['as' => 'reports.daily', 'uses' => 'MainController@dailyReport']);
                $router->post('/reports/hourly', ['as' => 'reports.hourly', 'uses' => 'MainController@hourlyReport']);

                $router->get('/payments/pending', ['as' => 'payments.pending', 'uses' => 'MainController@pendingPayments']);

                $router->get('/search/customers', ['as' => 'search.customers', 'uses' => 'MainController@searchCustomers']);
                $router->get('/search/drivers', ['as' => 'search.drivers', 'uses' => 'MainController@searchDrivers']);
                $router->get('/search/business', ['as' => 'search.business', 'uses' => 'MainController@searchBusiness']);

                $router->get('/settings', ['as' => 'settings', 'uses' => 'MainController@settings']);
                $router->post('/settings/application', ['as' => 'settings.application', 'uses' => 'MainController@settingsApplication']);
                $router->post('/settings/mail', ['as' => 'settings.mail', 'uses' => 'MainController@settingsMail']);
                $router->post('/settings/url', ['as' => 'settings.url', 'uses' => 'MainController@settingsUrl']);
                $router->post('/settings/firebase/project', ['as' => 'settings.project', 'uses' => 'MainController@settingsFirebaseProject']);
                $router->post('/settings/firebase/service-account', ['as' => 'settings.service-account', 'uses' => 'MainController@settingsFirebaseServiceAccount']);
                $router->post('/settings/google', ['as' => 'settings.google', 'uses' => 'MainController@settingsGoogle']);
                $router->post('/settings/paystack', ['as' => 'settings.paystack', 'uses' => 'MainController@settingsPaystack']);
                $router->post('/settings/sms', ['as' => 'settings.sms', 'uses' => 'MainController@settingsSms']);
                $router->post('/settings/transactions/fee', ['as' => 'settings.transaction-fee', 'uses' => 'MainController@settingsTransactionFee']);
                $router->post('/settings/cancellation/fee', ['as' => 'settings.cancellation-fee', 'uses' => 'MainController@settingsCancellationFee']);
                $router->post('/settings/verify-me', ['as' => 'settings.verify-me', 'uses' => 'MainController@settingsVerifyMe']);
                $router->delete('/settings/reset', ['as' => 'settings.reset', 'uses' => 'MainController@settingsReset']);
            });
        });
    });


});

$router->post('/paystack/webhook', ['as' => 'paystack.webhook', 'uses' => 'TransactionController@store']);

$router->post('/verify-me/webhook', ['as' => 'verify-me.webhook', 'uses' => 'UserController@verifyMeWebhook']);

// $router->get('/@{userReferral}', function($userReferral) {
//     return $userReferral;
// });