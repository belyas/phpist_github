/**
 * 2017 - 2018 PHPIST
 *
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future.
 *
 *  @author    PHPIST <yassine.belkaid87@gmail.com>
 *  @copyright 2017 - 2018 PHPIST
 *  @license   MIT
 */

$(document).ready(function() {
	$('#PHPIST_GITHUB_COMMITS').on('keyup', function() {
        if (isNaN($(this).val()) == true) {
            $(this).val(10);
            alert(pg_mum_warning_msg);
            return false;
        }
    });
});
