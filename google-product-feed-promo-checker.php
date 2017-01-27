<?php
	Class CHGooglePromotionsDiff {
		
		public $email = '';
		public $feedURL = '';
		
		public function RenameOldFeed() {
			if(file_exists('files/oldFeed.txt')) {
				exec('rm -f files/oldfeed.txt');
				rename('files/newFeed.txt', 'files/oldFeed.txt');
			}
		}
		
		public function GetNewFeed($url) {
			exec('cd files && wget -O newFeed.txt '.$url);
		}
		
		public function ParseToCSV($uri) {
			$data = array_map(
				function($v){
					return str_getcsv($v, "|");
				},
				file($uri)
			);
			array_shift($data);
			return $data;
		}
		
		public function DiffFiles($file1, $file2) {
			$arr1 = $this->ParseToCSV($file1);
			$arr2 = $this->ParseToCSV($file2);
			
			$combine = array_combine($arr1[0], $arr2[0]);
			$changePromoIDArray = array();
			foreach ($arr1 as $line) {
				$ident = $line[3];
				for ($i=1; $i<=count($arr2); $i++) {
					$j = $i - 1;
					
					
					if (in_array($ident, $arr2[$j])) { // ARE WE LOOKING IN THE RIGHT ARRAY?
						if ((empty($line[19])) && (!empty($arr2[$j][19]))) { // IS YESTERDAYS EMPTY, BUT TODAYS ISN'T?
							$string = $ident.' - NEW PROMO ID.';
							array_push($changePromoIDArray, $string);
						} else if ((!empty($line[19])) && (empty($arr2[$j][19]))) { // IS TODAYS EMPTY, BUT YESTERDAYS ISN'T?
							$string = $ident.' - PROMO ID REMOVED.';
							array_push($changePromoIDArray, $string);
						} else if ((!empty($line[19])) && (!empty($arr2[$j][19])) && ($arr2[$j][19] !== $line[19])) {
							$string = $ident.' - DIFFERENT PROMO ID\'S';
							array_push($changePromoIDArray, $string);
						}
						
					}
					
				}
				
			}
			
			return $changePromoIDArray;
			
		}
		
		public function EmailPromoIDChanges($changes) {
			
			$emailContent = '';
			
			foreach($changes as $key => $lineItem) {
				$emailContent .= $lineItem."\r\n";
			}
			
			if($emailContent == '') {
				$emailContent = 'No Promo Changes';
			}
			mail($this->email, 'Appliance City Promo Scan', $emailContent);
			
		}
		
	}
	
	$CHGooglePromotionsDiff = new CHGooglePromotionsDiff();
	
	$CHGooglePromotionsDiff->RenameOldFeed();
	$CHGooglePromotionsDiff->GetNewFeed($CHGooglePromotionsDiff->feedURL);
	
	$emailContent = $CHGooglePromotionsDiff->DiffFiles('files/newFeed.txt', 'files/oldFeed.txt');
	$CHGooglePromotionsDiff->EmailPromoIDChanges($emailContent);
	
	echo '<h1>Script Executed</h1>';