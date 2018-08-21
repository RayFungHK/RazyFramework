<?php

/*
 * This file is part of RazyFramework.
 *
 * (c) Ray Fung <hello@rayfung.hk>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return [
	// Custom ``library`` path, set '' or false to use default path
	'library_path' => '',

	// Custom ``module`` path, set '' or false to use default path
	'module_path' => '',

	// A callback function to identify the SSL protocol. Like Cloudflare,
	// it using a proxy to get the content via port 80 and transfer to the
	// end user via port 443 in `Flexible SSL` mode. Unfortunately, the $_SERVER['PORT']
	// still return 80 and $_SERVER['HTTPS'] return off so that you can't determine
	// the protocol is using HTTPS correctly.
	'identify_ssl' => null,

	// Force Razy Frameworks use https protocal
	'force_ssl' => false,
];
