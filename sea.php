<?php
error_reporting(0);
define('FLN',"sea.txt");//strange effect - define(FLN,"sea.txt") without quotes causes ternary not to work
define('FLS',"opt.txt");
define('FLV',"lvl.php");
define('PFX',"sea");
function map($aMap,$bLnk=false,$bSho=false,$sMcl="",$iSz=20){//draws a map
    if(!$aMap)for($i=0;$i<$iSz;$i++)$aMap[]=array_fill(0,$iSz,0);
    foreach($aMap as $i=>$mRow){
        foreach($mRow as $j=>$mCol){
            $sTtl=($i+1)."-".($j+1);
            switch($mCol){
                case 1:
                    $sCls=($bSho)?"mat":"wat";//ship
                    break;
                case -1:
                    $sCls=$sTtl="hit";//partially broken
                    break;
                case -2:
                    $sCls=$sTtl="fal";//fallen
                    break;
                case -3:
                    $sCls=$sTtl="mis";//miss
                    break;
                default:
                    $sCls="wat";//water
            }
            $sLnk=($bLnk&&!in_array($sCls,["hit","fal","mis"]))?"<a href='?".PFX."[pos]=$i-$j'>&nbsp;</a>":"";
            $aRow[]="<td class='$sCls' title='$sTtl'>$sLnk</td>";
        }
        array_unshift($aRow,"<tr>");
        array_push($aRow,"</tr>");
        $aRows[]=implode("",$aRow);
        unset($aRow);
    }
    $sMcl=($sMcl)?" $sMcl":"";
    $aRes="<table class='map{$sMcl}'>".implode("",$aRows)."</table>";
    return $aRes;
}
function chk(array $aMap=null,array $aHis=null){//recursive check of shooting result (hit, fall or miss)
    $bLiv=false;
    $mTmp=null;
    if(isset($aHis["nxt"]))list($i,$j)=array_shift($aHis["nxt"]);//get position for check
    if(isset($i)&&isset($j)){
        $aHis["his"][]=$sPos="$i-$j";//add current position to recursion history
        $iRes=$aMap[$i][$j];
        $aChk=[[$i-1,$j],[$i,$j+1],[$i+1,$j],[$i,$j-1]];//top, right, bottom, left
        foreach($aChk as $aPos){
            $ic=$aPos[0];
            $jc=$aPos[1];
            $iCur=$aMap[$ic][$jc];
            if(!in_array("$ic-$jc",$aHis["his"])){//if not in checking history
                if($iCur==1){//normal side found
                    $bLiv=true;
                    break;
                }
                if($iCur==-1)//broken side found - add next check position if it is not in history
                    $aHis["nxt"][]=[$ic,$jc];
            }
        }
        if($aHis["nxt"])$mTmp=chk($aMap,$aHis);//recursive call
        if($mTmp["liv"])$bLiv=true;
    }
    $aRes["his"]=$aHis;
    $aRes["liv"]=$bLiv;
    $aRes["ver"]=$i;
    $aRes["hor"]=$j;
    $aRes["pos"]=$sPos;
    $aRes["res"]=$iRes;
    return $aRes;
}
function cal(array $aMap=null,array $aHis=null){//shoot
    $iRes=$sPos=null;
    if($aHis&&is_array($aHis)&&$aMap&&is_array($aMap)){
        $aTmp=chk($aMap,$aHis);
        $sPos=$aTmp["pos"];
        if($aTmp["res"])$iRes=($aTmp["liv"])?-1:-2;
        else $iRes=-3;
        $aMap[$aTmp["ver"]][$aTmp["hor"]]=$iRes;
    }
    $aRes["map"]=$aMap;
    $aRes["res"]=$iRes;
    $aRes["pos"]=$sPos;
    return $aRes;
}
function sca(array $aMap=null,$bWat=true){//find free shootable positions for computer shoot or count all hittable cells
    $mRes=null;
    if($aMap&&is_array($aMap))
        foreach($aMap as $i=>$mRow)
            foreach($mRow as $j=>$mCol)
                if($bWat===false&&$mCol==1)$mRes++;//count hittable cells
                elseif($bWat===true&&in_array($mCol,[0,1]))$mRes[]="$i-$j";//get shootable position
    return $mRes;
}
function nxt(array $aMap=null,$sLst=null){//find next position(s) for computer shoot
    $aNxt=$sNxt=$k=null;
    $iWid=count($aMap[0]);
    $iHei=count(array_keys($aMap));
    if($aMap&&is_array($aMap)){
        if($sLst){
            list($i,$j)=explode("-",$sLst);//get last shot position
            if(($i-1)>0)$aChk[]=[$i-1,$j];//top
            if(($j+1)<$iWid)$aChk[]=[$i,$j+1];//right
            if(($i+1)<$iHei)$aChk[]=[$i+1,$j];//bottom
            if(($j-1)>0)$aChk[]=[$i,$j-1];//left
            foreach($aChk as $aPos){
                $ic=$aPos[0];
                $jc=$aPos[1];
                $iCur=$aMap[$ic][$jc];
                if($iCur>=0)//if not negative (can shoot, 0 - water or 1 - ship)
                    $aNxt[]="$ic-$jc";
            }
        }else{//randomize
            $aFre=sca($aMap);//get free cells to shoot
            while($aFre&&!in_array($sNxt,$aFre)){
                $ic=rand(0,$iHei-1);
                $jc=rand(0,$iWid-1);
                $sNxt="$ic-$jc";
            }
            if($sNxt)$aNxt[]=$sNxt;
        }
    }
    return $aNxt;
}
function cpu(array $aMps=null){//computer shooting logic
    $aRes=$sPos=null;
    if($aMps&&is_array($aMps)){
        $aNxt=($aNxt)?:nxt($aMps["pla"]);//randomize
        if($aNxt)$aTmp["nxt"][]=explode("-",array_shift($aNxt));//aiming
        $aRes=cal($aMps["pla"],$aTmp);//shoot
        $aMps["pla"]=$aRes["map"];//change user map
        if($aRes["res"]==-1&&!$aNxt)$aMps["nxt"]=nxt($aMps["pla"],$aRes["pos"]);//prepare for shoot around if hit and no next steps found
        elseif($aNxt&&$aRes["res"]!=-2)$aMps["nxt"]=$aNxt;//continue shooting around if not fall and next steps found
        else unset($aMps["nxt"]);//clear next steps for next random
        if(in_array($aRes["res"],[-1,-2]))$aMps["cpu_hit"]++;//count hits
        if($aRes["res"]==-2)$aMps["cpu_fal"]++;//count falls
        if($aRes["res"])$aMps["cpu_cnt"]++;//count shots
    }
    return $aMps;
}
function pla(array $aMps=null,$sPos=null){//player shooting logic
    $aRes=null;
    if($aMps&&is_array($aMps)){
        if(in_array($sPos,sca($aMps["cpu"])))$aTmp["nxt"][]=explode("-",$sPos);//aiming only on free cells
        $aRes=cal($aMps["cpu"],$aTmp);//shoot
        $aMps["cpu"]=$aRes["map"];//change computer map
        if(in_array($aRes["res"],[-1,-2]))$aMps["pla_hit"]++;//count hits
        if($aRes["res"]==-2)$aMps["pla_fal"]++;//count falls
        if($aRes["res"]){
            $aMps["pla_cnt"]++;//count shots
            $aMps["act"]=true;
        }else unset($aMps["act"]);//no user act
    }
    return $aMps;
}
function put(array $aMap=null,array $aShp=null,$bPut=false){//find free positions and get random position for a ship; optionally try to place
    $aRes=$sPos=null;
    if($aShp&&is_array($aShp)&&$aMap&&is_array($aMap)){
        $iWid=count($aShp[0]);//ship width
        $iHei=count(array_keys($aShp));//ship height
        $iWmp=count($aMap[0]);//map width
        $iHmp=count(array_keys($aMap));//map height
        $iWmx=$iWmp-$iWid;//max width to check
        $iHmx=$iHmp-$iHei;//max height to check
        foreach($aMap as $i=>$mRow){
            if($i>($iHmx))break;
            $iHt=($i>0)?$i-1:0;//top height (min map height correction)
            $iHb=($i<$iHmx)?$iHei+$i:$iHmp-1;//bottom height (max map height correction)
            foreach($mRow as $j=>$mCol){
                if($j>($iWmx))break;
                $iWt=($j>0)?$j-1:0;//top width (min map width correction)
                $iWb=($j<$iWmx)?$iWid+$j:$iWmp-1;//bottom width (max map width correction)
                $bFre=true;//assume that area is free
                for($k=$iHt;$k<=$iHb;$k++)
                    for($l=$iWt;$l<=$iWb;$l++)
                        if($aMap[$k][$l]>0){
                            $bFre=false;//if not free
                            break;//break checking area
                        }
                if($bFre)$aTmp[]="$i-$j";//if free add to allowed list
            }
        }
        if($aTmp)$sPos=$aTmp[rand(0,count($aTmp)-1)];//get random position
    }
    if($sPos&&$bPut){
        list($i,$j)=explode("-",$sPos);//get position for check
        if(isset($i)&&isset($j))
            foreach($aShp as $k=>$mRow)
                foreach($mRow as $l=>$mCol)$aMap[$i+$k][$j+$l]=$mCol;//copy ship to the position
    }
    $aRes["fre"]=$aTmp;
    $aRes["pos"]=$sPos;
    $aRes["map"]=$aMap;
    return $aRes;
}
function flp(array $aShp=null){//flip a ship horizontally
    $aRes=null;
    if($aShp&&is_array($aShp))foreach($aShp as $i=>$mRow)$aRes[]=(is_array($mRow))?array_reverse($mRow):$mRow;//if row is not an array then add it like a row
    return $aRes;
}
function rot(array $aShp=null){//rotate a ship (read matrix from bottom to top for each column)
    $aRes=null;
    if($aShp&&is_array($aShp)){
        $iWid=count($aShp[0]);
        $iHei=count(array_keys($aShp));
        for($i=0;$i<$iWid;$i++)
            for($j=$iHei-1;$j>=0;$j--)
                $aRes[$i][]=$aShp[$j][$i];
    }
    return $aRes;
}
function rnd($iSz=4){//generate map with ships randomly
    $iSz=(in_array($iSz,[4,5,6]))?$iSz:4;//default biggest ship size; range is 4-6
    $aShps=[
6=>[
[[1,1,1,1,1,1]],
[[1,1,1,1,1],
[1,0,0,0,0]],
[[1,1,1,0,0],
[0,0,1,1,1]],
[[1,1,1,1],
[1,0,0,0],
[1,0,0,0]],
[[0,1,0,0],
[1,1,1,1],
[0,1,0,0]],
[[1,1,1,1],
[1,1,0,0]],
[[1,1,1],
[1,0,0],
[1,1,0]],
[[1,0,1],
[1,1,1],
[0,1,0]],
[[1,1,1],
[1,1,0],
[1,0,0]],
[[1,1,1],
[1,1,0],
[0,1,0]],
[[1,1,1],
[1,1,1]]],
5=>[
[[1,1,1,1,1]],
[[1,1,1,1],
[1,0,0,0]],
[[1,1,0,0],
[0,1,1,1]],
[[0,1,0],
[1,1,1],
[0,1,0]],
[[1,1,1],
[1,0,0],
[1,0,0]],
[[1,1,0],
[0,1,1],
[0,0,1]],
[[1,1,1],
[0,1,0],
[0,1,0]],
[[1,1,1],
[1,0,1]],
[[1,1,1],
[1,1,0]]],
4=>[
[[1,1,1,1]],
[[1,1,1],
[1,0,0]],
[[1,1,0],
[0,1,1]],
[[1,1,1],
[0,1,0]],
[[1,1],
[1,1]]],
3=>[
[[1,1,1]],
[[1,1],
[1,0]]],
2=>[[[1,1]]],
1=>[[[1]]]
];
    $iSzm=9;
    $bRep=$j=null;
    do{
        $iSzm++;
        unset($aMap,$j,$bRep);//new map, ship count and repeat flag
        for($k=0;$k<$iSzm;$k++)$aMap[]=array_fill(0,$iSzm,0);//sea square
        for($i=$iSz;$i>0;$i--){//size of ships is decreasing
            $aShp=$aShps[$i][rand(0,count($aShps[$i])-1)];//get random ship configuration
            if($bRot=rand(0,1))$aShp=rot($aShp);//rotate
            if($bFlp=rand(0,1))$aShp=flp($aShp);//flip
            $j++;//ship count is increasing
            for($k=$j;$k>0;$k--){
                $aTmp=put($aMap,$aShp,true);//try to put a ship on the map
                if($aTmp["pos"])$aMap=$aTmp["map"];//if ship is placed then update the map
                else{
                    $bRep=true;//else repeat the placement on a bigger map
                    break;
                }
            }
            if($bRep)break;
        }
    }while($bRep);
    $mRes["map"]=$aMap;
    return $mRes;
}
function gen($iSz=4,$bRnd=true){//get maps with ships
    $mRes=null;
    if($bRnd){
        if($aTmp=rnd($iSz))$mRes["pla"]=$aTmp["map"];
        if($aTmp=rnd($iSz))$mRes["cpu"]=$aTmp["map"];
    }else{
        $mRes=(!$mRes)?include(FLV):$mRes;//try to read from file
        if(!$mRes){
            $mRes["pla"]=[
[0,0,0,0,0,0,0,0,1,0],
[0,1,1,0,0,0,1,0,1,0],
[0,0,1,0,0,0,0,0,0,0],
[0,0,1,0,0,0,0,0,1,0],
[1,0,0,0,0,1,0,0,0,0],
[0,0,0,0,1,1,0,0,0,0],
[0,0,1,0,0,0,0,0,1,0],
[0,0,1,0,0,0,0,0,1,0],
[0,0,0,0,1,1,0,0,1,0],
[0,1,0,0,0,0,0,0,0,0]
];
            $mRes["cpu"]=[
[0,0,1,0,0,0,0,1,1,0],
[0,0,0,0,0,0,0,0,0,0],
[0,1,1,1,1,0,0,0,0,0],
[0,0,0,0,0,0,0,0,1,0],
[1,0,0,0,0,1,0,0,0,0],
[0,0,0,0,0,1,0,0,0,0],
[0,0,1,0,0,0,0,0,1,0],
[0,1,1,0,0,0,0,0,1,0],
[0,0,0,0,1,1,0,0,0,0],
[0,1,0,0,0,1,0,0,0,0]
];
        }
    }
    return $mRes;
}
function get($sFnm=FLN,$bArr=true){//reads last unserialized array of a file
    //$mRes=($aLns=file($sFnm,FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES))?array_pop($aLns):null;//strange error - empty array even if line exist
    $mRes=($sLns=file_get_contents($sFnm))?preg_split('/\\r\\n?|\\n/',$sLns):null;//split lines
    if($mRes&&is_array($mRes))
        for($i=count($mRes)-1;$i>=0;$i--)//check from bottom
            if($sLn=$mRes[$i])break;//for non-empty line
    $mRes=($sLn&&$bArr)?unserialize($sLn):null;
    return $mRes;
}
function set($sStr=null,$sFnm=FLN,$iFlg=FILE_APPEND){//save string in file
    $mRes=($sStr)?file_put_contents($sFnm,"$sStr\n",$iFlg):null;
    return $mRes;
}
function del($sFnm=FLN){//delete file
    $mRes=unlink($sFnm);
    return $mRes;
}
function mnu(){//game menu
    $sMnu=null;
    $aOpt=get(FLS);//get game options
    $aOpt["lng"]=($_REQUEST[PFX]["lng"])?:$aOpt["lng"];//gui language
    $aOpt["lng"]=($aOpt["lng"])?:"en";//language default
    $aOpt["msz"]=($_REQUEST[PFX]["msz"])?:$aOpt["msz"];//map size
    $aOpt["msz"]=($aOpt["msz"])?:4;//map size default
    $aOpt["gsz"]=($_REQUEST[PFX]["gsz"])?:$aOpt["gsz"];//map size
    $aOpt["gsz"]=($aOpt["gsz"])?:"mid";//map size default
    $aOpt["des"]=($_REQUEST[PFX]["des"])?:$aOpt["des"];//map design
    $aOpt["des"]=($aOpt["des"])?:"stl";//map design default
    $mRes=set(serialize($aOpt),FLS,0);//save game options, 0 - no flags (replace file)
    $aLng=[
//English words
"en"=>[
"game_new"=>"New game",
"game_options"=>"Options",
"game_info"=>"Information",
"game_resume"=>"Resume game",
"msz"=>"Battleship size",
"des"=>"Map design",
"blk"=>"Block",
"stl"=>"Real",
"vec"=>"Vector",
"gsz"=>"Map size",
"sml"=>"Small",
"mid"=>"Middle",
"big"=>"Big",
"lng"=>"Language",
"sqr"=>"block(s)",
"en"=>"English",
"ru"=>"Русский",
"dsc"=>'
The game "Sea battle" is step-by-step strategy,<br>
which was very popular in our childhood, when we draw maps and ships on sheets of squared papers,<br>
telling to each other the coordinates of rocket landing. Who first breaks all ships of enemy,<br>
saving by a "miracle" at least one own ship, wins the game.<br>
In this version of game the opponent is a simple computer logic.<br>
Enjoy the game!',
"lic"=>'
The MIT License (MIT)
<br>
Copyright (c) 2017 Vyacheslav Badmatsirenov (Sl One)
<br>
Portions copyright Sergey Badmatsirenov
<br>
Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
<br>
The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
<br>
<br>
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.',
"tnx"=>'
Thanks for support to:<br>
father,<br>
S.Badmatsirenov,<br>
I.Hamaganov,<br>
T.Hamaganova<br>
And thanks for playing!',
"cnt"=>"
E-mail: vibam@yandex.ru<br>
Site: http://graf.esy.es<br>",
"mnu"=>"Menu",
"gnm"=>"Sea battle"
],
//Russian words
"ru"=>[
"game_new"=>"Новая игра",
"game_options"=>"Опции",
"game_info"=>"Информация",
"game_resume"=>"Продолжить",
"msz"=>"Размер линкора",
"des"=>"Вид карты",
"blk"=>"Блоки",
"stl"=>"Реальный",
"vec"=>"Вектор",
"gsz"=>"Размер карты",
"sml"=>"Маленький",
"mid"=>"Средний",
"big"=>"Большой",
"lng"=>"Язык интерфейса",
"sqr"=>"блок(а/ов)",
"en"=>"English",
"ru"=>"Русский",
"dsc"=>'
Игра "Моркой бой" представляет собой пошаговую стратегию,<br>
которой мы когда-то увлекались в детстве, рисуя карты и кораблики на листочках бумаги в клеточку,<br>
поочередно называя друг другу координаты посадки ракеты. Кто первым разбивал все корабли противника,<br>
при этом "чудом" сохранив хотя бы один свой, выигрывал в бою.<br>
В данной версии игры оппонентом выступает простая компьютерная логика.<br>
Приятной игры!',
"lic"=>'
Лицензия MIT
<br>
Copyright (c) 2017 Вячеслав Бадмацыренов (Sl One)
<br>
Частичные права Сергей Бадмацыренов
<br>
Данная лицензия разрешает лицам, получившим копию данного программного обеспечения и сопутствующей документации (в дальнейшем именуемыми «Программное Обеспечение»), безвозмездно использовать Программное Обеспечение без ограничений, включая неограниченное право на использование, копирование, изменение, слияние, публикацию, распространение, сублицензирование и/или продажу копий Программного Обеспечения, а также лицам, которым предоставляется данное Программное Обеспечение, при соблюдении следующих условий:
<br>
Указанное выше уведомление об авторском праве и данные условия должны быть включены во все копии или значимые части данного Программного Обеспечения.
<br>
<br>
ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ, ВКЛЮЧАЯ ГАРАНТИИ ТОВАРНОЙ ПРИГОДНОСТИ, СООТВЕТСТВИЯ ПО ЕГО КОНКРЕТНОМУ НАЗНАЧЕНИЮ И ОТСУТСТВИЯ НАРУШЕНИЙ, НО НЕ ОГРАНИЧИВАЯСЬ ИМИ. НИ В КАКОМ СЛУЧАЕ АВТОРЫ ИЛИ ПРАВООБЛАДАТЕЛИ НЕ НЕСУТ ОТВЕТСТВЕННОСТИ ПО КАКИМ-ЛИБО ИСКАМ, ЗА УЩЕРБ ИЛИ ПО ИНЫМ ТРЕБОВАНИЯМ, В ТОМ ЧИСЛЕ, ПРИ ДЕЙСТВИИ КОНТРАКТА, ДЕЛИКТЕ ИЛИ ИНОЙ СИТУАЦИИ, ВОЗНИКШИМ ИЗ-ЗА ИСПОЛЬЗОВАНИЯ ПРОГРАММНОГО ОБЕСПЕЧЕНИЯ ИЛИ ИНЫХ ДЕЙСТВИЙ С ПРОГРАММНЫМ ОБЕСПЕЧЕНИЕМ.',
"tnx"=>'
Благодарность за поддержку:<br>
отцу,
С.Бадмацыренову,<br>
И.Хамаганову,<br>
Т.Хамагановой
<br>
И спасибо за то, что играли!',
"cnt"=>"
E-mail: vibam@yandex.ru<br>
Site: http://graf.esy.es<br>",
"mnu"=>"Меню",
"gnm"=>"Морской бой"
]];
    extract($aLng[$aOpt["lng"]],EXTR_PREFIX_ALL,"sLng");//create language variables
    $sResume=(get())?"<li><a href='?".PFX."[nxt]=y'>$sLng_game_resume</a></li>":"";
    if($_REQUEST[PFX]["mnu"]){
        $sMnu="<ul class='mnu'>
            <li><a href='?".PFX."[new]=y'>$sLng_game_new</a></li>$sResume
            <li><a href='?".PFX."[opt]=y'>$sLng_game_options</a></li>
            <li><a href='?".PFX."[inf]=y'>$sLng_game_info</a></li>
            </ul>";
    }elseif($_REQUEST[PFX]["opt"]){
        $sMnu="<ul class='mnu opt'>
            <li><a href='?".PFX."[opt_msz]=y'>$sLng_msz</a></li>
            <li><a href='?".PFX."[opt_gsz]=y'>$sLng_gsz</a></li>
            <li><a href='?".PFX."[opt_des]=y'>$sLng_des</a></li>
            <li><a href='?".PFX."[opt_lng]=y'>$sLng_lng</a></li>
            </ul>";
    }elseif($_REQUEST[PFX]["opt_msz"]){
        $sMnu="<ul class='mnu opt'>
            <li><a href='?".PFX."[msz]=4&".PFX."[mnu]=y'".(($aOpt["msz"]==4)?" class='act'":"").">4 $sLng_sqr</a><table class='map'><tr><td></td><td></td><td></td><td></td></tr></table></li>
            <li><a href='?".PFX."[msz]=5&".PFX."[mnu]=y'".(($aOpt["msz"]==5)?" class='act'":"").">5 $sLng_sqr</a><table class='map'><tr><td></td><td></td><td></td><td></td><td></td></tr></table></li>
            <li><a href='?".PFX."[msz]=6&".PFX."[mnu]=y'".(($aOpt["msz"]==6)?" class='act'":"").">6 $sLng_sqr</a><table class='map'><tr><td></td><td></td><td></td><td></td><td></td><td></td></tr></table></li>
            </ul>";
    }elseif($_REQUEST[PFX]["opt_gsz"]){
        $sMnu="<ul class='mnu opt'>
            <li><a href='?".PFX."[gsz]=sml&".PFX."[mnu]=y'".(($aOpt["gsz"]=="sml")?" class='act'":"").">$sLng_sml</a></li>
            <li><a href='?".PFX."[gsz]=mid&".PFX."[mnu]=y'".(($aOpt["gsz"]=="mid")?" class='act'":"").">$sLng_mid</a></li>
            <li><a href='?".PFX."[gsz]=big&".PFX."[mnu]=y'".(($aOpt["gsz"]=="big")?" class='act'":"").">$sLng_big</a></li>
            </ul>";
    }elseif($_REQUEST[PFX]["opt_des"]){
        $sMnu="<ul class='mnu opt'>
            <li><a href='?".PFX."[des]=blk&".PFX."[mnu]=y'".(($aOpt["des"]=="blk")?" class='act'":"").">$sLng_blk</a></li>
            <li><a href='?".PFX."[des]=stl&".PFX."[mnu]=y'".(($aOpt["des"]=="stl")?" class='act'":"").">$sLng_stl</a></li>
            <li><a href='?".PFX."[des]=vec&".PFX."[mnu]=y'".(($aOpt["des"]=="vec")?" class='act'":"").">$sLng_vec</a></li>
            </ul>";
    }elseif($_REQUEST[PFX]["opt_lng"]){
        $sMnu="<ul class='mnu opt'>
            <li><a href='?".PFX."[lng]=en&".PFX."[mnu]=y'".(($aOpt["lng"]=="en")?" class='act'":"").">$sLng_en</a></li>
            <li><a href='?".PFX."[lng]=ru&".PFX."[mnu]=y'".(($aOpt["lng"]=="ru")?" class='act'":"").">$sLng_ru</a></li>
            </ul>";
    }elseif($_REQUEST[PFX]["inf"]){
        $sMnu="<ul class='mnu inf'>
            <li>$sLng_dsc</li>
            <li>$sLng_lic</li>
            <li>$sLng_tnx</li>
            <li>$sLng_cnt</li>
            <li><a href='?".PFX."[mnu]=y'>$sLng_mnu</a></li>
            </ul>";
    }else $sMnu="<form><button class='mnu' name='".PFX."[mnu]' value='y' type='submit'>$sLng_mnu</button></form>";
    return $sMnu;
}
function sea(){//sea battle game logic
    $sRes=$aTmp=$sMes=$sGui=null;
    $bCli=true;//cpu's map click-able
    $sMnu=mnu();//get menu
    $aOpt=get(FLS);//get game options
    $aLng=[
//English words
"en"=>[
"pla"=>"Player",
"cpu"=>"Computer",
"win"=>"wins",
"game_new"=>"New game",
"res_for"=>"Results for",
"shot"=>"Shots",
"hit"=>"Hits",
"fall"=>"Falls",
"all"=>"All",
"gnm"=>"Sea battle"
],
//Russian words
"ru"=>[
"pla"=>"Игрок",
"cpu"=>"Компьютер",
"win"=>"победил",
"game_new"=>"Новая игра",
"res_for"=>"Результаты для",
"shot"=>"Выстрелов",
"hit"=>"Попаданий",
"fall"=>"Разбито",
"all"=>"Всего",
"gnm"=>"Морской бой"
]];
    extract($aLng[$aOpt["lng"]],EXTR_PREFIX_ALL,"sLng");//create language variables
    if($_REQUEST[PFX]["new"])del();//clear history
    $aMps=(get())?:gen($aOpt["msz"]);//max ship size
    $aMps["pla_all"]=($aMps["pla_all"])?:sca($aMps["pla"],false);//get player's hittable cells count
    $aMps["cpu_all"]=($aMps["cpu_all"])?:sca($aMps["cpu"],false);//get cpu's hittable cells count
    if($aMps["pla_hit"]<$aMps["cpu_all"])$aMps=pla($aMps,$_REQUEST[PFX]["pos"]);//calculate player's action
    if($aMps["pla_hit"]==$aMps["cpu_all"])$bCli=false;
    if($aMps["act"]&&$bCli)$aMps=cpu($aMps);//if user act and not won then cpu move
    if($aMps["cpu_hit"]==$aMps["pla_all"])$bCli=false;
    if(!$bCli){
        $sWnm=($aMps["pla_hit"]==$aMps["cpu_all"])?$sLng_pla:$sLng_cpu;
        $sMes="<div class='mes'>$sWnm {$sLng_win}!<br><a href='?".PFX."[new]=y' class='mes $sWnm'>{$sLng_game_new}?</a></div><br>";
    }
    $sHdr="<tr><td>{$sLng_res_for}</td><td>{$sLng_shot}</td><td>{$sLng_hit}</td><td>{$sLng_fall}</td><td>{$sLng_all}</td></tr>";
    $sUsr="<tr><td>$sLng_pla</td><td>{$aMps['pla_cnt']}</td><td>{$aMps['pla_hit']}</td><td>{$aMps['pla_fal']}</td><td>{$aMps['cpu_all']}</td></tr>";
    $sCpu="<tr><td>$sLng_cpu</td><td>{$aMps['cpu_cnt']}</td><td>{$aMps['cpu_hit']}</td><td>{$aMps['cpu_fal']}</td><td>{$aMps['pla_all']}</td></tr>";
    $sSum="<br><table class='summary'>$sHdr{$sUsr}$sCpu</table><br>";
    $sCpu_map=map($aMps["cpu"],$bCli,false,implode(" ",[$aOpt["gsz"],$aOpt["des"],"cpu"]));
    $sPla_map=map($aMps["pla"],false,true,implode(" ",[$aOpt["gsz"],$aOpt["des"]]));
    if($aMps["act"])$aTmp=set(serialize($aMps));//if user act then save game
    $sMps="<table class='maps'><tr><th>$sLng_pla</th><th>$sLng_cpu</th></tr><tr><td>$sPla_map</td><td>$sCpu_map</td></tr></table>";
    $sGnm="<div class='ttl'><a href='".$_SERVER["PHP_SELF"]."'>$sLng_gnm</a></div>";
    $sImg=(!$_REQUEST[PFX]&&file_exists(__DIR__."/pic/sea.jpg"))?"<img class='sea' src='./pic/sea.jpg' title='$sLng_gnm'>":"";
    $sGui=($_REQUEST[PFX]["pos"]||$_REQUEST[PFX]["nxt"]||$_REQUEST[PFX]["new"])?$sMes.$sMps.$sSum:"";
    $sRes=$sGnm.$sImg.$sGui.$sMnu;
    $aRes["HTM"]=$sRes;
    $aRes["GNM"]=$sLng_gnm;
    return $aRes;
}
$mRes=sea();?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv=Content-Type content="text/html;charset=UTF-8">
<title><?php echo $mRes["GNM"]?></title>
</head>
<body>
<style type="text/css">
div.sea{
    width: 100%;
    text-align: center;
}
img.sea{
    width: 50%;
    height: auto;
    margin-top: 20px;
    margin-bottom: 20px;
}
.ttl{
    font-size: xx-large;
    color: darkblue;
    margin: 30px;
}
.ttl a{
    text-decoration: none;
}
.summary{
    margin: 30px;
}
table.map{
    border: 1px green solid;
    border-spacing: 0;
    margin: 20px;
}
table.map td{
    width: 1em;
    height: 1em;
    padding: 0;
}
.map.sml td,.map.sml a{
    width: 20px;
    height: 20px;
}
.map.mid td,.map.mid a{
    width: 40px;
    height: 40px;
}
.map.big td,.map.big a{
    width: 60px;
    height: 60px;
}
.map a:hover{
    -webkit-filter: brightness(300%);
    filter: brightness(300%);
    box-shadow: inset 0px 0px 10px rgba(255,255,0,1);
    -webkit-box-shadow: inset 0px 0px 10px rgba(255,255,0,1);
    -moz-box-shadow: inset 0px 0px 10px rgba(255,255,0,1);
}
table.summary{
    display: inline-table;
    width: 50%;
}
table.maps{
    display: inline-block;
}
.mnu{
    padding: 0;
}
.mnu table{
    display: inline-block;
    vertical-align: bottom;
    margin: 0 0 0 20px;
    border-spacing: 0;
}
.mnu table td{
    border: 1px green solid;
    box-sizing: border-box;
    line-height: 0;
}
button.mnu{
    padding: 10px;
    width: 10em;
    color: blue;
    background: lightseagreen;
    font-weight: bolder;
}
button.mnu:hover{
    -webkit-filter: brightness(300%);
    filter: brightness(300%);
    box-shadow: inset 0px 0px 10em rgba(0,0,255,1);
    -webkit-box-shadow: inset 0px 0px 10em rgba(0,0,255,1);
    -moz-box-shadow: inset 0px 0px 10em rgba(0,0,255,1);
}
li{
    display: block;
    padding: 20px;
}
.map a{
    display: block;
    width: 1em;
    height: 1em;
    text-decoration: none;
}
.mes{
    font-size: xx-large;
    color: red;
}
a.mes{
    display: inline;
    color: blue;
    text-decoration: none;
}
a.act{
    color: red;
}
td.mat{
    background: white;
}
td.hit{
    background: pink;
}
td.fal{
    background: red;
}
td.mis{
    background: grey;
}
td.wat{
    background: blue;
}
.stl td.mat{
    background: url(./pic/stl/shp.jpg) center center / contain no-repeat white;
}
.stl.cpu td.mat{
    background: url(./pic/stl/shp1.jpg) center center / contain no-repeat white;
}
.stl td.hit{
    background: url(./pic/stl/shp_hit.jpg) center center / contain no-repeat pink;
}
.stl.cpu td.hit{
    background: url(./pic/stl/shp1_hit.jpg) center center / contain no-repeat pink;
}
.stl td.fal{
    background: url(./pic/stl/shp_fal.jpg) center center / contain no-repeat red;
}
.stl.cpu td.fal{
    background: url(./pic/stl/shp1_fal.jpg) center center / contain no-repeat red;
}
.stl td.mis{
    background: url(./pic/stl/mis.jpg) center center / contain no-repeat grey;
}
.stl td.wat{
    background: url(./pic/stl/wat.jpg) center center / contain no-repeat blue;
}
.vec td.mat{
    background: url(./pic/vec/shp.png) center center / contain no-repeat blue;
}
.vec td.hit{
    background: url(./pic/vec/hit.png) center center / contain no-repeat blue;
}
.vec td.fal{
    background: url(./pic/vec/fal.png) center center / contain no-repeat blue;
}
.vec td.mis{
    background: url(./pic/vec/mis.png) center center / contain no-repeat blue;
}
.vec td.wat{
    background: url(./pic/vec/wat.png) center center / contain no-repeat blue;
}
</style>
<div class='sea'><?php echo $mRes["HTM"]?></div>
</body></html>