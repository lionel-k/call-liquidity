 <?php
    $id_history = 2;
    $connect = mysqli_connect("localhost","root","");
	$db = mysqli_select_db($connect,"liquidity");
	$sql = mysqli_query($connect,"SELECT DISTINCT rate FROM offers WHERE id_history = '$id_history' ORDER BY rate DESC");
	$rates = array();
	$banks = array();
	$totals = array();
	while($rate_tab = mysqli_fetch_array($sql))
	{
		$rate = $rate_tab['rate'];
		$rates[] = $rate_tab['rate'];
		$sql2 = "SELECT DISTINCT name FROM banks ORDER BY name;";
		$res1 = mysqli_query($connect,$sql2);
	    while($bank = mysqli_fetch_array($res1))
		{
			$name = $bank['name'];
			$banks[$rate][$name]=0;
			$sql2 = "select rate,offers.amount as amount,banks.name as name_bank 
			from offers inner join banks on banks.id = offers.id_bank 
			where rate = '$rate' and banks.name='$name' and id_history = '$id_history';";
			$res2 = mysqli_query($connect,$sql2) or die ("erreur2");
			while ($montant = mysqli_fetch_array($res2))
			{
				
				$amount=$montant['amount'];
				$banks[$rate][$name]=$amount;//constitution du tableau des taux, nom de banque et montant
				$totals[$rate] = array_sum($banks[$rate]);
			}
		}
	}
	$previous_rate = 0;
	$cumul[$previous_rate] = 0;
	$cumulf = array();
	$residuel = array();
	foreach($totals AS $rate => $amount)
	{
		$cumul[$rate] = $cumul[$previous_rate] + $totals[$rate];
		$previous_rate=$rate;
		$cumulf[$rate] = $cumul[$rate];//contient le cumul à chaque rate
	}
	$sql3 = "select int_amount from history_offers where id = '$id_history';";
	$res3 = mysqli_query($connect,$sql3) or die ("erreur3");
	while($resid = mysqli_fetch_array($res3))
	{
		$intervention = $resid['int_amount'];
	}
	foreach($cumulf AS $rate => $amount)
	{
		$residuel[$rate] = $intervention - $cumulf[$rate];
	}
	/* afficher*/
	$final=array($banks,$totals,$cumulf,$residuel);//tous les tableaux en un seul.
	echo '<pre>';
	print_r($final);
	echo '</pre>';
	/*adjudication*/
	echo '</br>';
	echo "ADJUDICATION</br>";
	echo "----------------------</br>";
	$i = 0;
	$j = 0;
	$adjud_rate =0;
	$ad_amount = 0;
	$adjud = $banks;
	foreach($residuel as $rate => $amount)
	{
		if ($amount > 0)
		{
			$j = $rate;
			$ad_amount = $amount;
		}
		if($amount <= 0)
		{
			$temp[]=$rate;//tableau de rate où le résiduel est nul et/ou négatif
			$i = max($temp);
		}
	}
	foreach($adjud as $rate => $adjud_amount)
	{
		foreach($adjud_amount as $banks => $amount)
		{
			/*if($adjud_amount == $i)*/
			if($rate < $i)
			{
				$adjud[$rate][$banks]= 0;//annulation des montants non adjugés
			}
			if($residuel[$j] >0 and $residuel[$i] < 0)
			{
				$adjud[$i][$banks] = $amount*$ad_amount/$totals[$i];
			}
		}
	}
	echo '<pre>';
	print_r($adjud);
	echo'</pre>';
	/*notifications*/
	echo "NOTIFICATION!";
	echo '</br>';
	foreach ($adjud as $rate => $adjud_amount)
	{
		foreach($adjud_amount as $adjud => $amount)
		{
			if($amount > 0)
			{
				$adjud_banks[$adjud][$rate] = $amount;// tableau contenant les montants adjugés classés par banque
			}
		}
	}
	echo '<pre>';
	print_r($adjud_banks);
	echo '</pre>';
	for($offset=0;$offset<sizeof($adjud_banks);$offset++)
	{
		echo '<pre>';
		print_r(array_slice($adjud_banks,$offset,true));//montants adjugé d'une banque
		echo '</pre>';
	}
?>