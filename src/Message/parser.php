<?php //
//         // Get the contents
//         $html = file_get_contents($path);
//         $html = com_gorad_util_Text_Cast::toASCII($html);
//         $dat = Array();

//         // Extract the derived files.
//         $tmp = explode('<!--X-Derived:', $html);
//         array_shift($tmp);
//         $dat['derived'] = '';
//         foreach ($tmp as $filename) {
//             $filename = explode('-->', $filename, 2);
//             $dat['derived'] .= ',' . trim($filename[0]);
//         }
//         $dat['derived'] = trim($dat['derived'], ',');

//         // Clean the HTML
//         $tidy = new com_gorad_UI_Tidy();
//         $tidy->parseString($tmp);
//         $tidy->cleanRepair();

//         // Do the final cleaning and save it.
//         $tmp = trim($tidy->getString());
//         $tmp = strtr($tmp, Array(' class="MsoNormal"' => '', '&amp;' => '&'));
//         $dat['body'] = $tmp;

//         // Get the prev-next navigation
//         $tmp = $this->_getFilePart($html, 'X-BotPNI');
//         foreach (explode('<li>', $tmp) as $link) {
//             if (preg_match('~prev by date.*href="(.*)"~is', $link, $arr)) {
//                 $dat['prevbydate'] = preg_replace('~[^0-9]~', '', $arr[1]);
//             } elseif (preg_match('~next by date.*href="(.*)"~is', $link, $arr)) {
//                 $dat['nextbydate'] = preg_replace('~[^0-9]~', '', $arr[1]);
//             } elseif (preg_match('~prev.*by thread.*href="(.*)"~is', $link, $arr)) {
//                 $dat['prevbythread'] = preg_replace('~[^0-9]~', '', $arr[1]);
//             } elseif (preg_match('~next by thread.*href="(.*)"~is', $link, $arr)) {
//                 $dat['nextbythread'] = preg_replace('~[^0-9]~', '', $arr[1]);
//             }
//         }

//         // Trim down the body for a description.
//         $tmp = wordwrap(preg_replace("/(<[^>]*>|[\n\r])/s", ' ', $dat['body']), 256, " ...\n", false);
//         $tmp = explode("\n", $tmp, 2);
//         $tmp = trim($tmp[0]);
//         $dat['description'] = $tmp;

//         // Clean the URL
//         $dat['link'] = preg_replace('~msg([0-9]+)\.html~i', '$1', $this->get('link'));

//         // Get the followups and references
//         $dat['followups'] = $this->_getFilePart($html, 'X-Follow-Ups');
//         $dat['references'] = $this->_getFilePart($html, 'X-References');





// //     public function &update($conf)
// //     {
//         $els =& $this->getByRef('elements');

//         // Do replacements on all the elements.
//         if (is_array($conf['replace'])) {
//             foreach ($els as $k => &$v) {
//                 $v = strtr($v, $conf['replace']);
//             }
//         }

//         // Update the nav.
//         if (isset($conf['nav'])) {
//             $tpl = $conf['nav'];
//             foreach ($els as $k => &$v) {
//                 if (preg_match('/(next|prev)/', $k)) {
//                     $v = com_gorad_TemplateEngine_Litey::fetchStatic($tpl, Array('id' => $v));
//                 } elseif (preg_match('/followups|references/', $k)) {
//                     $v = strtr($v, Array(
//                         '&reg;' => '(R)',
//                         '&eacute;' => 'e',
//                         '&rsquo;' => "'",
//                         '&mdash;' => "--",
//                     ));
//                     $xml = com_gorad_data_XML::factory($v);
//                     foreach ($xml->xpath('//a') as $a) {
//                         $a['href'] = com_gorad_TemplateEngine_Litey::fetchStatic($tpl, Array('id' => $a['name']));
//                     }
//                     $v = $xml->asXML();
//                 }
//             }
//         }
