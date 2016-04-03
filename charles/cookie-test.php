<?php 



function get_content($url)
{
    $ch = curl_init();

    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_HEADER, true);
    // curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
    // 											'Accept-Language: en-US,en;q=0.8',
    // 											'Host: www.aliexpress.com',
				// 								'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
				// 								'Upgrade-Insecure-Requests: 1',    											
    // 										)
    // );
    curl_setopt ($ch, CURLOPT_USERAGENT, 'Frank/0.1 http://frank.elxr.it/bot.html');
    // curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36');
    // curl_setopt ($ch, CURLOPT_COOKIE, 'ali_apache_id=204.2.171.85.1453332854334.973260.3; acs_usuc_t=acs_rt=59fc9b33b2dd40d39b7cff0b311e3cae; xman_t=mb0ZAU87bRs5bdyNWWfDvZtmQRShuweHS9y9xocm9Sl+PbU4qc6riO6ErLhLA/n/; xman_f=daJRsdcvOXC+WlR7oc0MsU4PyN78Em4+sLIqTq7a1nMrZ5NBcjJdWeVAhsvtdjRVeKeUgnZUGsy3AIKC+PP5Z7wCWyfHfuyX0sZzNd9M7f5KPsdIKh4sMg==; ali_beacon_id=204.2.171.85.1453332854334.973260.3; __utma=3375712.1025062580.1453332856.1453332856.1453332856.1; __utmb=3375712.1.10.1453332856; __utmc=3375712; __utmz=3375712.1453332856.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); cna=eQcnD24Y/3QCAUFYWMs5wA0a; l=Aq6u8F2el3I6zypRhcCcAYeSfg5w-3Kp; isg=2C6AA023C840DAEA9CF90253D04EA37A; d_ab_f=b6597f89a9b34f74bc4c8ebde4fee2ca; JSESSIONID=C02D6C1F0054AA1A8E4D1BB70CB3ED92; ali_apache_track=; ali_apache_tracktmp=; xman_us_f=x_l=0&x_locale=en_US; aep_usuc_f=region=US&site=glo&b_locale=en_US&c_tp=USD; intl_locale=en_US; intl_common_forever=HOaDhG0Qhhu4btfJbQ79LfPRLg5r6+yHJzV2Fz8xku1X/Kwjd5NXCw==');

    ob_start();

    curl_exec ($ch);
    curl_close ($ch);
    $string = ob_get_contents();

    ob_end_clean();
    
    return $string;     
}
$content = get_content('http://www.aliexpress.com/item/2014-Formal-Winter-Women-Slim-Thick-Double-Sided-High-Waisted-Outer-Wear-Down-Pants-Trousers/32215209572.html');
var_dump ($content);

/* Per SO suggestion to right-click on the request in Charles and select "Copy cURL Request", it worked! and when I dissect the headers of that request, they are*/
// Host: www.aliexpress.com
// Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8
// Upgrade-Insecure-Requests: 1
// User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36
// Accept-Language: en-US,en;q=0.8
// Cookie: ali_apache_id=204.2.171.85.1453332854334.973260.3; acs_usuc_t=acs_rt=59fc9b33b2dd40d39b7cff0b311e3cae; xman_t=mb0ZAU87bRs5bdyNWWfDvZtmQRShuweHS9y9xocm9Sl+PbU4qc6riO6ErLhLA/n/; xman_f=daJRsdcvOXC+WlR7oc0MsU4PyN78Em4+sLIqTq7a1nMrZ5NBcjJdWeVAhsvtdjRVeKeUgnZUGsy3AIKC+PP5Z7wCWyfHfuyX0sZzNd9M7f5KPsdIKh4sMg==; ali_beacon_id=204.2.171.85.1453332854334.973260.3; __utma=3375712.1025062580.1453332856.1453332856.1453332856.1; __utmb=3375712.1.10.1453332856; __utmc=3375712; __utmz=3375712.1453332856.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); cna=eQcnD24Y/3QCAUFYWMs5wA0a; l=Aq6u8F2el3I6zypRhcCcAYeSfg5w-3Kp; isg=2C6AA023C840DAEA9CF90253D04EA37A; d_ab_f=b6597f89a9b34f74bc4c8ebde4fee2ca; JSESSIONID=C02D6C1F0054AA1A8E4D1BB70CB3ED92; ali_apache_track=; ali_apache_tracktmp=; xman_us_f=x_l=0&x_locale=en_US; aep_usuc_f=region=US&site=glo&b_locale=en_US&c_tp=USD; intl_locale=en_US; intl_common_forever=HOaDhG0Qhhu4btfJbQ79LfPRLg5r6+yHJzV2Fz8xku1X/Kwjd5NXCw==



/*
-----------RESPONSE---------------------
using cookies sent in the Chrome response to URL http://www.aliexpress.com/item/Hot-Sale-Women-Pencil-Pants-Skinny-Zipper-Hollow-Out-Black-White-2015-New-Fashion-Casual-Slim/32322377471.html
*/

/*
curl_setopt ($ch, CURLOPT_COOKIE, 'JSESSIONID=75031E5340558D354D40EF78B7230802; ali_apache_track=; JSESSIONID=6627B1C537B1B35CBD9F0B6FB43A5B49; xman_us_f=x_l=0&x_locale=en_US; aep_usuc_f=region=US&site=glo&b_locale=en_US&c_tp=USD; ali_apache_tracktmp=; intl_common_forever=enNLuqYs0bms6rYTm13Ji+XU/AHE2AnQsGq7TqhUmDAqoEmYrnAnFw==; intl_common_forever=cdYz6ocL/455yHP1lWdAbF5VTWe/8/cTLZi/lSBk9boujZ47yNTn+A==; intl_locale=en_US; aep_usuc_f=region=US&site=glo&b_locale=en_US&c_tp=USD; xman_us_f=x_l=0&x_locale=en_US; ali_apache_tracktmp=; ali_apache_track=; intl_locale=en_US;');
$content = get_content('http://www.aliexpress.com/item/Hot-Sale-Women-Pencil-Pants-Skinny-Zipper-Hollow-Out-Black-White-2015-New-Fashion-Casual-Slim/32322377471.html');
*/
/*
Set-Cookie:JSESSIONID=75031E5340558D354D40EF78B7230802; Path=/; HttpOnly
Set-Cookie:ali_apache_track=; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/
Set-Cookie:JSESSIONID=6627B1C537B1B35CBD9F0B6FB43A5B49; Path=/; HttpOnly
Set-Cookie:xman_us_f=x_l=0&x_locale=en_US; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/
Set-Cookie:aep_usuc_f=region=US&site=glo&b_locale=en_US&c_tp=USD; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/
Set-Cookie:ali_apache_tracktmp=; Domain=.aliexpress.com; Path=/
Set-Cookie:intl_common_forever=enNLuqYs0bms6rYTm13Ji+XU/AHE2AnQsGq7TqhUmDAqoEmYrnAnFw==; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/; HttpOnly
Set-Cookie:intl_common_forever=cdYz6ocL/455yHP1lWdAbF5VTWe/8/cTLZi/lSBk9boujZ47yNTn+A==; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/; HttpOnly
Set-Cookie:intl_locale=en_US; Domain=.aliexpress.com; Path=/
Set-Cookie:aep_usuc_f=region=US&site=glo&b_locale=en_US&c_tp=USD; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/
Set-Cookie:xman_us_f=x_l=0&x_locale=en_US; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/
Set-Cookie:ali_apache_tracktmp=; Domain=.aliexpress.com; Path=/
Set-Cookie:ali_apache_track=; Domain=.aliexpress.com; Expires=Wed, 16-Feb-2084 02:59:31 GMT; Path=/
Set-Cookie:intl_locale=en_US; Domain=.aliexpress.com; Path=/
*/

/*

*/


/*
-----------RESPONSE---------------------
using cookies sent in the Chrome request of URL http://www.aliexpress.com/item/Hot-Sale-Women-Pencil-Pants-Skinny-Zipper-Hollow-Out-Black-White-2015-New-Fashion-Casual-Slim/32322377471.html
*/

/*
curl_setopt ($ch, CURLOPT_COOKIE, 'ali_apache_id=204.2.171.85.1453936754535.995928.9; acs_usuc_t=acs_rt=f48a455163ae457283a52e42b4c378a8; ali_beacon_id=204.2.171.85.1453936754535.995928.9; cna=dT4wD9aZh1cCAUFYWMt7a8gF; xman_t=quPg00G83/rhC5j6Pud9vu6TMOKjXM9rZmCcc35zp7iA/i4XQ9ZeNXo9KMQFa+CyPE4ZHBb9CtcSIZx0RK55PnJBmSgjWUmzAasu/xFohSE=; xman_f=wicTrA4bxT9jTvxC9Z8+MNH/roDNrJPaTzKJ1xqLoMOnwRWlZzi7X2iiZJkezwIW4mNeGxf2mvCVMGxXtmM2Q9vfqoJcSyJS7rkgKIJioQiB8CTTA+VrBA==; d_ab_f=aea6d305588d43adbc84bea1ed1778e5; aep_history=keywords%5E%0Akeywords%09%0A%0Aproduct_selloffer%5E%0Aproduct_selloffer%0932322377471; JSESSIONID=76D6F23185198A6A3EEF99FA4EBC7272; xman_us_f=x_l=0&x_locale=en_US; intl_locale=en_US; intl_common_forever=56CmaR3x+CbLS94l95jBmDfTCpK8yfb3dKor52i8dhAAcnwBFnrPLw==; aep_usuc_f=site=glo&region=US&b_locale=en_US&c_tp=USD; __utma=3375712.1394171183.1453936757.1453940168.1454024095.3; __utmb=3375712.1.10.1454024095; __utmc=3375712; __utmz=3375712.1453936757.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); ali_apache_track=; ali_apache_tracktmp=; l=AggI5IioNttG94yD/47S2YF/WHwau2y7; isg=5CBBFC766B95831BFB635395EDBAB64B');
$content = get_content('http://www.aliexpress.com/item/Hot-Sale-Women-Pencil-Pants-Skinny-Zipper-Hollow-Out-Black-White-2015-New-Fashion-Casual-Slim/32322377471.html');
*/

/*
string(790) "HTTP/1.1 302 Moved Temporarily
Content-Type: text/html;charset=UTF-8
Content-Length: 0
Server: Apache-Coyote/1.1
P3P: CP="CAO PSA OUR"
Location: http://sec.aliexpress.com/query.htm?smApp=aedetail&smPolicy=aedetail-detail_item-anti_Spider-htmlrewrite-checklogin&smCharset=UTF-8&smTag=NjUuODguODguMjAzLCwzNWMzMWIxMDk2NjM0ZjkwOWU5MDMxNzdiMjZiNmUwMg%3D%3D&smReturn=http%3A%2F%2Fwww.aliexpress.com%2Fitem%2FHot-Sale-Women-Pencil-Pants-Skinny-Zipper-Hollow-Out-Black-White-2015-New-Fashion-Casual-Slim%2F32322377471.html&smSign=fsdJqLfMDpeoqb6zzsGl2g%3D%3D&smLocale=en_US
Content-Language: en-US
Access-Control-Allow-Origin: http://hz.aliexpress.com
Date: Thu, 28 Jan 2016 23:49:46 GMT
Connection: keep-alive
Set-Cookie: JSESSIONID=5D86D87BC3234F62A237E419E79154D9; Path=/; HttpOnly

"
*/


/*
-----------RESPONSE---------------------
using recorded charles proxy session of http://www.aliexpress.com/item/Hot-Sale-Women-Pencil-Pants-Skinny-Zipper-Hollow-Out-Black-White-2015-New-Fashion-Casual-Slim/32322377471.html
to figure out why visiting that URL works in my browser but not via cURL or file_get_contents
*/

/*
curl_setopt ($ch, CURLOPT_COOKIE, 'ali_apache_id=204.2.171.85.1453332854334.973260.3; acs_usuc_t=acs_rt=59fc9b33b2dd40d39b7cff0b311e3cae; xman_t=mb0ZAU87bRs5bdyNWWfDvZtmQRShuweHS9y9xocm9Sl+PbU4qc6riO6ErLhLA/n/; xman_f=daJRsdcvOXC+WlR7oc0MsU4PyN78Em4+sLIqTq7a1nMrZ5NBcjJdWeVAhsvtdjRVeKeUgnZUGsy3AIKC+PP5Z7wCWyfHfuyX0sZzNd9M7f5KPsdIKh4sMg==; ali_beacon_id=204.2.171.85.1453332854334.973260.3; __utma=3375712.1025062580.1453332856.1453332856.1453332856.1; __utmb=3375712.1.10.1453332856; __utmc=3375712; __utmz=3375712.1453332856.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); cna=eQcnD24Y/3QCAUFYWMs5wA0a; l=Aq6u8F2el3I6zypRhcCcAYeSfg5w-3Kp; isg=2C6AA023C840DAEA9CF90253D04EA37A; d_ab_f=b6597f89a9b34f74bc4c8ebde4fee2ca; JSESSIONID=C02D6C1F0054AA1A8E4D1BB70CB3ED92; ali_apache_track=; ali_apache_tracktmp=; xman_us_f=x_l=0&x_locale=en_US; aep_usuc_f=region=US&site=glo&b_locale=en_US&c_tp=USD; intl_locale=en_US; intl_common_forever=HOaDhG0Qhhu4btfJbQ79LfPRLg5r6+yHJzV2Fz8xku1X/Kwjd5NXCw==');
*/

/*
string(503) "HTTP/1.1 302 Moved Temporarily
Server: Tengine
Content-Type: text/html
Content-Length: 258
Location: http://204.2.171.15/
Access-Control-Allow-Origin: http://hz.aliexpress.com
Date: Thu, 28 Jan 2016 00:15:10 GMT
Connection: keep-alive

<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
<head><title>302 Found</title></head>
<body bgcolor="white">
<h1>302 Found</h1>
<p>The requested resource resides temporarily under a different URI.</p>
<hr/>Powered by Tengine</body>
</html>
"
*/