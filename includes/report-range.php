<?php
function report_range($from,$to):array{
 foreach([$from,$to] as $date){if(!is_string($date)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date))throw new InvalidArgumentException('Tanggal harus valid (YYYY-MM-DD).');$d=DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$d||$d->format('Y-m-d')!==$date||$date<'1000-01-01'||$date>'9999-12-30')throw new InvalidArgumentException('Tanggal tidak valid.');}
 if($from>$to)throw new InvalidArgumentException('Dari Tanggal tidak boleh melewati Sampai Tanggal.');return[$from.' 00:00:00',(new DateTimeImmutable($to))->modify('+1 day')->format('Y-m-d').' 00:00:00'];}
