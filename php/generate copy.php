 /*
        $parts = [];

        $last_pos = 0;

        for($i=0; $i<count($positions); $i++){

            $pos = $positions[$i];

            $_part = substr($html, $pos);
            $part_text = substr($_part, 8, strpos($_part, "[@end]")-8);

            $part_before = substr($html, $last_pos, $pos - $last_pos);

            $parts[] = $part_before;
            $parts[] = $part_text;

            $last_pos = $pos + strlen($part_text) + 8 + 6;
        }

        $parts[] = substr($html, $last_pos);

        $htmls = ['' => ''];

        $if_object_code = "IF OBJECT ID:";
        $if_not_object_code = "IF NOT OBJECT ID:"; 

        $key_comma = "";

        for($i=1; $i<count($parts)-1; $i+=2){

            $new_htmls = [];

            $old_keys = array_keys($htmls);

            for($j=0; $j<count($old_keys); $j++){

                $_html = $htmls[$old_keys[$j]];

                $first = $_html.$parts[$i-1];
                $second = $_html.$parts[$i-1];
                
                if(strpos($parts[$i], $if_object_code)!==false){

                    preg_match_all('/\{'.$if_object_code.'(\d+)\}/', $parts[$i], $matches);

                    $first .= str_replace($matches[0], '', $parts[$i]);

                    $id = $matches[1][0];

                    $new_key_first = "YES_".$id;
                    $new_key_second = "NO_".$id;
                }                 
                else if(strpos($parts[$i], $if_not_object_code)!==false){
                    preg_match_all('/\{'.$if_not_object_code.'(\d+)\}/', $parts[$i], $matches);

                    $first .= str_replace($matches[0], '', $parts[$i]);
    
                    $id = $matches[1][0];

                    $new_key_first = "NO_".$id;
                    $new_key_second = "YES_".$id;
                }

                $first .= $parts[$i+1];
                $second .= $parts[$i+1];                
                
                $new_htmls[$old_keys[$j].$key_comma.$new_key_first] = $first;
                $new_htmls[$old_keys[$j].$key_comma.$new_key_second] = $second;

                $key_comma = "*";
            }

            $htmls = $new_htmls;         
        }

        $page_suffix = 1;

        $new_keys = array_keys($htmls);

        $html_page_nrs = [];

        $distribute_links = [];

        for($i=0; $i<count($new_keys); $i++){

            $_html = $htmls[$new_keys[$i]];              

            $filename = "$pageNr"."(".$page_suffix.")".".html";
            $filePath = "$outputDir/$filename";

            $sub_page_nr = (int)$pageNr."(".$page_suffix.")";

            $html_page_nrs[$new_keys[$i]] = $sub_page_nr ;

            $replacements = [
                '{PAGE_NR}'    => $sub_page_nr 
            ];    

            $_html = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $_html
            );

            // todo: die replaces kannman bestimmt woanders besser vornehmen:()
            $_html = str_replace('[@begin]','', $_html);
            $_html = str_replace('[@end]','', $_html);
            $_html = str_replace('<div></div><br/>','', $_html);  

            if(!$generate_only_link_id || $generate_only_link_id==$linkId){
                file_put_contents($filePath, $_html);
                echo "✅ Generiert: $filename<br>\n";
            }

            $pageFiles[] = $filename;

            $page_suffix++;

            $distribute_links [$new_keys[$i]] = [];

            $key_splitted = explode('*', $new_keys[$i]);

            foreach($key_splitted as $link_condition){
                $distribute_links [$new_keys[$i]][] = $link_condition;
            }
        }

        $distribute_pagetext = "";

        for($i=0; $i<count($new_keys); $i++){

            $distribute_pagetext .= "<div>Wenn ";
            $distribute_pagetext .= "<ul>";

            $distribute_link = $distribute_links[$new_keys[$i]];

            foreach($distribute_link as $link_condition){

                if($link_condition != '0'){

                    $obj_id = explode('_', $link_condition)[1];

                    if(strpos($link_condition, 'YES') !== false){
                        $obj_text = $objects[$obj_id]['if_text'];
                    }
                    else {//NO
                        $obj_text = $objects[$obj_id]['if_not_text'];
                    }

                    $distribute_pagetext .= "<li>".$obj_text."</li>";
                } 
            }

            $distribute_pagetext .= "</ul>";
            $distribute_pagetext .= "dann gehe zu Seite ".$html_page_nrs[$new_keys[$i]].".</div>";   

            $distribute_pagetext .= "</br>";
        }

        $replacements = [
            '{TITLE}'      => "Verteilerseite ".$placeName,
            '{PLACE_NR}' => $placeId,
            '{PLACE_NAME}' => $placeName,
            '{PAGE_TEXT}'  => $distribute_pagetext,
            '{PAGE_NR}'  => (int)$pageNr
        ];

        $distributer_html = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $distribute_template
        );

        //verteilerseite erstellen
        $filename = "$pageNr.html";
        $filePath = "$outputDir/$filename";

        if(!$generate_only_link_id || $generate_only_link_id==$linkId){
            file_put_contents($filePath, $distributer_html);
            echo "✅ Generiert: $filename<br>\n";
        }
        
        echo "✅ Generiert: $filename<br>\n";

        $pageFiles[] = $filename;           
        */