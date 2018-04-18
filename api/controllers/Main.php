<?php
defined('BASEPATH') OR exit('No Access');
$m = '@KEYmTypemSndGoodsTimeStamp@TIMESTAMPuCode@KEY';
//echo strtoupper(md5($m));
/**
 * 网店管家ERP API
 *@API_SECRET 密钥  
 *@API_UCODE  验证码
*/
define("API_SECRET","API_SECRET");
define("API_UCODE","UCODE");
class Main extends CI_Controller {
    /**
     * 请求参数
     * @mOrderSearch(抓取订单)
     * @mGetOrder(订单详情查询)
     * @mSndGoods(订单发货通知)
     * @mGetGoods (商品查询)
     * @mSysGoods (更新库存)
     */
    protected $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    protected $xml_tag = "";
    private $msg_code;
  /**
   * 校验 SECRET 
   */    
    private  function checkAuth($data=array()){
       if(empty($data)) return false;
       $sign_str = API_SECRET.'mType'.$data['mType'].'TimeStamp'.$data['TimeStamp'].'uCode'. API_UCODE .API_SECRET;
       
       $sign = strtoupper(md5($sign_str));
      
       if($sign==$data['Sign']){
          return true;
       }
       return false;
    }
	public function Index()
	{  
	   $uCode = $this->input->post("uCode");
	   $mType = $this->input->post("mType");
       $Sign  = $this->input->post("Sign");
       $TimeStamp = $this->input->post("TimeStamp");
	   
       $data['Sign'] = $Sign;
       $data['mType'] = $mType;
       $data['TimeStamp'] = $TimeStamp;
       
       //校验
       if(!$this->checkAuth($data)){
          echo $this->ToXml();
          exit();
       }
       
       //判断请求参数是否有效
       if(empty($mType)){
          echo $this->ToXml();
          exit();
       }
      //执行操作
      switch($mType){
        case "mOrderSearch":
          $this->mOrderSearch();
        break;
        case "mGetOrder":
          $this->mGetOrder();
        break;
        case "mSndGoods":
          $this->mSndGoods();
        break;
        case "mGetGoods":
          $this->mGetGoods();
        break;
        case "mSysGoods":
          $this->mSysGoods();
        break;
      }

       
	}
    /** 查询订单  Y(必须参数)
     * @OrderStatus Y 订单状态(状态有2种。1表示已付款未发货（含货到付款）；0表示未付款未发货)
     * @PageSize Y 订单分页 当前采用分页返回数量和页数会一起传
     * @Start_Modified 订单修改开始时间  格式 YYYY-MM-DD HH:II:SS
     * @End_Modified  订单修改结束时间
     * @return 参数
     * @OrderCount 订单总数
     * @Result 是否成功  1成功，0失败
     * @Page
     * @OrderNO 订单好 数组格式 OrderList
     */
    private function mOrderSearch(){
      
      $order_status = (int)$this->input->post("OrderStatus");
      $PageSize = (int)$this->input->post("PageSize");
	  $Page = (int)$this->input->post("Page"); 
	  if($Page<=1){
		  $Page = 0;
	  }else{
		  $Page = $Page-1;
	  }
	  $PageSize = $PageSize>0 ? $PageSize : 1000;
	  $Pages = $Page*$PageSize;
	  $result = 0;
	  $time = date("Y-m-d");
	  $end_time = date("Y-m-d",time()+3600*12);
      $Start_Modified = $this->input->post("Start_Modified");
      $End_Modified = $this->input->post("End_Modified");
	  $start = !empty($Start_Modified) ? strtotime($Start_Modified) : strtotime($time);
	  $end = !empty($End_Modified) ? strtotime($End_Modified) : strtotime($end_time);
	
	 
	 // $where = "pay_status={$order_status} AND order_status=0 AND UNIX_TIMESTAMP(time)>={$start} AND UNIX_TIMESTAMP(time)<={$end}";
	  $order = $this->db->where("order_status",1)->where("pay_status",$order_status)->where("create_date>=",$start)->where("create_date<=",$end)->limit($PageSize,$Pages)->get("order")->result_array();
	   $total = $this->db->where("order_status",1)->where("pay_status",$order_status)->where("create_date>=",$start)->where("create_date<=",$end)->get("order")->num_rows();

	  if(!empty($order)){
		  foreach($order as $k=>$v){
			  $return['OrderList'][] = array('OrderNO'=>$v['order_no']);
			
		  }
		$result = 1;  
	  }
	  
      $return['Page'] = $Page+1;
	  $return['Result'] = $result;
	  $return['Cause'] = $result==1 ? '订单查询成功':'订单查询失败';
	  $return['OrderCount'] = $total;
     
      echo $this->array2xml($return,"Order");
	  
	  exit();
      
      
      
    }
    
    /**
     * 查询订单详情
     * @orderNO 接受POST参数
     * return 
     * <?xml version='1.0' encoding='utf-8'?>
        <Order>
        <Result>1</Result>
        <Cause></Cause>
        <OrderNO>B88174</OrderNO>          //必填
        <OrderStatus>WAIT_BUYER_PAY</OrderStatus>
        <DateTime>2015-01-26 15:08:35</DateTime> //严格按照时间格式
        <BuyerID><![CDATA[ecshop]]></BuyerID>
        <BuyerName><![CDATA[刘先生]]></BuyerName>
        <Country><![CDATA[中国]]></Country>
        <Province><![CDATA[北京]]></Province>
        <City><![CDATA[北京]]></City>
        <Town><![CDATA[海淀区]]></Town>
        <Adr><![CDATA[海兴大厦]]></Adr>
        <Zip><![CDATA[]]></Zip>
        <Email><![CDATA[ecshop@ecshop.com]]></Email>
        <Phone><![CDATA[13986765412]]></Phone>
        <Total>1403</Total>  //没有金额时请输出0
        <logisticsName><![CDATA[申通快递]]></logisticsName>
        <chargetype><![CDATA[银行收款]]></chargetype>
        <PayAccount><![CDATA[银行汇款/转帐]]></PayAccount>
        <PayID><![CDATA[2]]></PayID>
        <Postage>15.00</Postage>   //没有邮费金额时请输出0
        <CustomerRemark><![CDATA[]]></CustomerRemark>
        <Remark><![CDATA[;银行汇款/转帐已付：1403.00;未付:0.00,]]></Remark>
        <InvoiceTitle><![CDATA[]]></InvoiceTitle>
        <Item> //多个商品时请输出多个item节点
        <oid>4</oid>
        <GoodsID><![CDATA[ECS000000]]></GoodsID>
        <GoodsName><![CDATA[KD876]]></GoodsName>
        <Price>1388.00</Price> //没有金额时请输出0
        <GoodsSpec>白色</GoodsSpec>
        <Count>1</Count>
        </Item>
        <Item>
        <oid>5< oid>
        <GoodsID><![CDATA[ECS0020000]]></GoodsID>
        <GoodsName><![CDATA[KD2876]]></GoodsName>
        <Price>138.00</Price> //没有金额时请输出0
        <GoodsSpec>黑色</GoodsSpec>
        <GoodsStatus>WAIT_SELLER_AGREE</GoodsStatus>
        <Count>1</Count>
        </Item>
        </Order>
     *
     */
    private function mGetOrder(){
        $order_no = $this->input->post("OrderNO");
		$order =  $this->db->where('order_no',$order_no)->get("order")->row_array();
		if(!empty($order)){
		    $order['order_info'] = $this->db->where("orderid",$order['orderid'])->get("order_detail")->result_array();
        }
		
		$return['Result'] = 0;
        $return['Cause'] = '订单详情查询失败';
		if(!empty($order)){
          $return['OrderNO'] = $order['order_no'];
          $return['Result'] = 1;
          $return['Cause'] = '订单详情查询成功';
		  /**
		   		WAIT_BUYER_PAY（等待买家付款），
				WAIT_SELLER_SEND_GOODS（买家已付款），
				WAIT_BUYER_CONFIRM_GOODS（卖家已发货），
				TRADE_FINISHED（交易成功），
				TRADE_CLOSED（付款以后用户退款成功，交易自动关闭）
				例：<OrderStatus> WAIT_BUYER_PAY </OrderStatus >
           */
		  if($order['order_status']==0 && $order['pay_status']==0){
			  $order_status = 'WAIT_BUYER_PAY';
		  }elseif($order['order_status']==1 && $order['pay_status']==1){
			  $order_status = 'WAIT_SELLER_SEND_GOODS';
		  }elseif($order['order_status']==2 && $order['pay_status']==1){
			  $order_status = 'WAIT_BUYER_CONFIRM_GOODS';
		  }elseif($order['order_status']==8 && $order['pay_status']==1){
			  $order_status = 'TRADE_FINISHED';
		  }elseif($order['order_status']==99 && $order['pay_status']==0){
			  $order_status = 'TRADE_CLOSED';
		  }
		  //解析地址信息;
		  $address_info = !empty($order['address_info']) ? json_decode($order['address_info'],true) : null;

          $return['OrderStatus'] = $order_status;
          $return['DateTime'] = date('Y-m-d H:i:s',$order['create_date']);
          $return['BuyerID'] = $order['userid'];
          $return['BuyerName'] = $address_info['name'];
          $return['Country'] = '中国';
		  
          if($address_info){
			 //$address = explode(",",$order['address']);
			 $Province = $address_info['province'];
			 $City = $address_info['city'];
			 $Town = $address_info['town'];
			 $Adr = $address_info['detail'];
		  }
		  $return['Province'] = $Province;
		  $return['City'] =  $City;
		  $return['Town'] = $Town;
		  $return['Adr'] = $Adr;
		  $return['Zip'] = "";
          $return['Phone'] = $address_info['phone'];
          $return['Total'] = number_format($order['pay_price']+$order['postage'],2,'.','');
          $return['Email'] = '';
		  if(!empty($order['post_info'])){
			  $logisticsName = json_decode($order['post_info'],true);
			  $return['LogisticsName'] = $logisticsName['name'];
		  }
          
          $return['Chargetype'] = '担保交易';
          $return['Postage'] = $order['postage'];//邮费'0.00';
          
		  
		  $return['Remark'] = '备注';//;
		  $return['CustomerRemark'] = '';//$order['remark'];
		  //解析JSON 购买信息
		  if(!empty($order['order_info'])){
			  $cartdata = $order['order_info'];//json_decode($order['order_info'],true);

			  foreach($cartdata as $k=>$v){

				   $sn = "";
				   if(!empty($v['sku_id'])){ //有SKU信息 ，读取SKU编码
                       $goods = $this->db->select("sku_sn")->where("sku_id",$v['sku_id'])->get("sku")->row_array();
                       $sn = $goods['sku_sn'];
				   }else{ //否则 读取商品的SKU编码
				       $goods = $this->db->select("goods_sn")->where("goods_id",$v['goods_id'])->get("goods")->row_array();
                       $sn = $goods['goods_sn'];
                   }
			   $return['Item'][] = array(
			      'oid'=>$v['goods_id'],
				  'GoodsID'=>$sn,
				  'Price'=>!empty($v['goods_price'])?$v['goods_price'] : 0,
				  'GoodsName'=>$v['goods_name'],
				  'GoodsSpec'=>$v['sku_name'],
				  'GoodsStatus'=>'',
				  'Count'=>$v['goods_number']
			    );
			  }
		  }

        }  
      echo $this->array2xml($return,"Order");
	  die();
    }
    /** 发货通知
     * 接受参数
     * @OrderNO 订单号，多个“,”隔开
     * @pack_items 发货包裹清单多个商品用"|"隔开, 为空则视为整单发货
                    包含商品单编号和商品发货数量，格式：oid:count|oid:count,
                    发货数量需为大于0的整数
     * @SndStyle 快递公司名; 见接口附表
     * @SndCode 快递公司 英文代码
     * @BillID  快递单号;
     * return 返回参数
     * @result 1成功，0失败
     * @Cause  信息描述说明
     */
    private function mSndGoods(){
          $order_no = $this->input->post("OrderNO");
          $pack_items = $this->input->post("pack_items");
          $SndStyle = $this->input->post("SndStyle");
          $SndCode = $this->input->post("SndCode");
          $BillID = $this->input->post("BillID");
          
          $return['Result'] = 0;
          $return['Cause'] = '发货失败';
		  $data['number'] = $BillID;


		  $delivery = $this->db->where("code",$SndCode)->get("delivery")->row_array();
		  if(!empty($delivery)){
		      $data['id'] = $delivery['id'];
              $data['code'] = $SndCode;
              $data['name'] = $SndStyle;
          }else{
              $data['id'] = 0;
              $data['code'] = 'Other';
              $data['name'] = '其他';
          }

		  //是否有多个订单号
		  $orderno = $order_no;
		  if(!empty($order_no)){
			  $orderno = explode(",",$order_no);
		  }
		  
	$order_info = $this->db->where_in('order_no',$orderno)->where('order_status',1)->where('pay_status',1)->get("order")->result_array();
	 if(!empty($order_info)){
		  //已发货
		  $order = $this->db->where_in('order_no',$orderno)->update('order',array('delivery_date'=>time(),'order_status'=>2,'post_info'=>json_encode($data)));

		  if($order){
			 $return['Result'] = 1;
			 $return['Cause'] = '发货成功'; 
			 
			 //$this->http_get($order_info['orderid']); //发货微信通知；
		  }
	 }
		  echo $this->array2xml($return);
		  die();
          
    }
    /** 商品查询
     * 接受参数 3选一
     * @GoodsName 支持模糊化查询 ，注：商品名称，商品状态，商品编码，一次只会传入一个状态
     * @GoodsType 1在售或者上架=OnsSale 2仓库中或者下架=InStock
     * @OuterID 包含多规格的子sku商家编码
     *  以上3选1
     * @PageSize
     * @Page;
     * --------------------------
     * @return  返回参数;
     * @TotalCount  总商品数量
     * @Result  1成功，0失败
     * @Cause  原因描述
     * --------------------------
     * @Ware 商品集合以下集合参数，请查API文档
     * @ItemID 主商品系统ID
     * @ItemName 商品名称
     * @Num  商品数量，如果为多规格，则为总数例：13
     * @Price 单价 没有数值输出0 例：12.8
     * @OuterID  商家外部编码
     * @IsSku  多规格商品; 1是，0否
     * -----------------------------------
     * @Items  IsSku 为1则传递 ites 商品明细数据集（包含多个Item节点）
     * @Unit  规格单位
     * @SkuID  规格ID ，但规格返回空
     * @Num  规格数量，没有返回0
     * @SkuOuterID 商家外部编码
     * @SkuPrice 商品Sku的价格 没有则返回0;
     */ 
    private function mGetGoods(){
          $GoodsName = trim($this->input->post("GoodsName"));
          $GoodsType = trim($this->input->post("GoodsType"));
          $OuterID =  trim($this->input->post("OuterID"));
          $PageSize = $this->input->post("PageSize");
          $Page = $this->input->post("Page");
          if($Page<=1){
			  $Page = 0;
		  }else{
			  $Page = $Page-1;
		  }
		  $PageSize = $PageSize>0 ? $PageSize : 1000;
		  $Page = $Page*$PageSize;
		   
		  $total = 0;
		  $goods = array();
		  if(!empty($GoodsName)){
			  
			 $where = "name like '%".$GoodsName."%'";
			 $goods =  $this->db->where($where)->order_by("create_time DESC")->get("goods")->result_array();
		  	 $total =  $this->db->where($where)->get("goods")->num_rows();
		  }elseif(!empty($OuterID)){
			 $where['goods_sn'] = $OuterID;
		     $goods =  $this->db->where($where)->order_by("create_time DESC")->get("goods")->result_array();
		      $total =  $this->db->where($where)->get("goods")->num_rows();
		  }elseif(!empty($GoodsType)){
			 
			  $where['goods_status'] = strtolower($GoodsType)=="onsale" ? 0 : 1;
			  $goods =  $this->db->where($where)->order_by("create_time DESC")->get("goods")->result_array();
		  	   $total =  $this->db->where($where)->get("goods")->num_rows();
		  }
		 
		  
		  $result = 2; //查询失败
		  if(!empty($goods)){
			  foreach($goods as $k=>$v){
				  $return['Ware'][$k]['ItemID'] = $v['goods_id'];
				  $return['Ware'][$k]['ItemName'] = $v['goods_name'];
				  $return['Ware'][$k]['Num'] = $v['stock'];
				  $return['Ware'][$k]['Price'] = $v['price'];
				  $return['Ware'][$k]['OuterID'] = $v['goods_sn'];
				  $return['Ware'][$k]['IsSku'] = !empty($v['sku_no']) ? 1 : 0;
				  if(!empty($v['sku_no'])){
					  $spec = $this->db->where("item_id",$v['goods_id'])->get("sku")->row_array();
					  foreach($spec as $key=>$val){

						$return['Ware'][$k]['Items'][$key]['Unit']= $val['sku_name'];
						$return['Ware'][$k]['Items'][$key]['SkuID'] = $val['sku_id'];
						$return['Ware'][$k]['Items'][$key]['Num'] = $val['sku_stock'];
						$return['Ware'][$k]['Items'][$key]['SkuOuterID'] = $val['sku_sn'];
						$return['Ware'][$k]['Items'][$key]['SkuPrice'] = $val['sku_price'];
					  }
					  
				  }
			  }
			$result = 1;  
		  }
		  
		  
		  
          $return['TotalCount'] = $total;
          $return['Result'] = $result; //1成功，2失败;
          $return['Cause'] = $return['Result']==1 ? '商品查询成功':'商品查询失败';
          
       echo  $this->array2xml($return,'GOODS');
	   
	   die();
    }
    
    /**
     * 商品库存更新通知
     * 请求参数
     * @ItemID  商品ID；
     * @SkuID 商品SkuID3
     * @Quantity 数量;
     *======================
     * @return  返回参数
     * @Result  1成功，0失败
     * @GoodsType  可不填写,OnSale在售（上架）；InStock仓库中（下架）失败，返回为空
     * @Cause  原因说明;
     */
     private function mSysGoods(){
        $ItemID = $this->input->post("ItemID");
        $SkuID = $this->input->post("SkuID");
        $Quantity = (int)$this->input->post("Quantity");
		
		$where ="1=1";
		if(!empty($ItemID)){
			$where .= ' AND goods_id='.$ItemID;
		}
		
		
		
		$goods = $this->db->where($where)->get("goods")->row_array();
		if(!empty($goods)){
			$state = 0;
			
			if(!empty($SkuID)){
				 $state =  $this->db->where("sku_id",$SkuID)->update("sku",array('sku_stock'=>$Quantity));
			}else{
				  $state =  $this->db->where($where)->update("goods",array('stock'=>$Quantity));
			}
        	
			if($state){
			  $return['GoodsType'] = empty($goods['goods_status']) ? "OnSale" : "InStock";
			  $return['Result'] = 1;
			  $return['Cause'] = "商品库存更新成功";
			}else{
				$return['GoodsType'] = "OnSale";
                $return['Result'] = 0;
               $return['Cause'] = "商品库存更新失败";
			}
		}else{
			$return['GoodsType'] = "OnSale";
            $return['Result'] = 0;
            $return['Cause'] = "商品库存更新失败";
		}
		
		
	    
        
        
         echo $this->array2xml($return);
		 exit();
     }
    
 
    
    /**
     * 组合成XML UTF8*/
    private function array2xml($data,$tag=""){
        $xml = $this->xml;
        if($tag==""){
          
          $xml .= "<Rsp>".$this->ToXml($data)."</Rsp>";
        
        }else{
           $xml .= "<{$tag}>" . $this->ToXml($data) . "</{$tag}>";
        }
        
        return $xml;
    }
    /**
     * 遍历数组 转XML
     * @data 序列化XML 数组
     * 
     * @return xml;
     * */
     
   	private function ToXml($data=array())
	{
	    $xml = "";
        $tmp ="";
        
		if(!is_array($data) || count($data) <= 0)
		{
    		$xml = $this->xml."<Rsp><Result>0</Result><Cause>Oauth Fail</Cause></Rsp>";
    	}else{

        	foreach ($data as $key=>$val)
        	{
                
                
        	    if(!empty($val) && is_array($val)){
        	        if(!is_numeric($key)){
                      $this->xml_tag = $key;
                    }

        	       if($this->xml_tag=='OrderList'){
        	          
        	           foreach($val as $k=>$v){
                        $xml .=  $this->toXml2($v);
                       }
                       $xml = "<{$this->xml_tag}>".$xml."</{$this->xml_tag}>"; 
        	       }elseif($this->xml_tag=="Item"){
            	       foreach($val as $k=>$v){
                        $xml .=  "<{$this->xml_tag}>".$this->toXml2($v)."</{$this->xml_tag}>"; 
                       }
                   }elseif($this->xml_tag=='Ware'){
                      foreach($val as $k=>$v){
                        $xml .=  "<{$this->xml_tag}>".$this->toXml2($v)."</{$this->xml_tag}>";
                        
                      }
                      
                   }
                  
        	    }else{
            		if (is_numeric($val)){
            			$xml.="<".$key.">".$val."</".$key.">";
            		}else{
            			$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            		}
                  
                }
                
                $xml .= $tmp;
                
            }
            
            
           
        }
        
      
        
        return $xml; 
	}
    
    private function toXml2($data=array()){
        $xml = "";
        $tmp ="";
        if(empty($data)) return false;
        foreach($data as $key=>$val){
            if(is_array($val)){
               
               foreach($val as $k=>$v){
                  $tmp .= '<Item>'.$this->toXml2($v)."</Item>"; 
               }
               $tmp = '<Items>'.$tmp."</Items>";
               
            }else{
               if (is_numeric($val)){
                	$xml.="<".$key.">".$val."</".$key.">";
                }else{
                	$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
                }
              
            }     
            $xml .=$tmp;
        }
      
       
        return $xml;
    }
    
    // curl请求发货微信通知;
	private function http_get($id){
		if(empty($id)) return false;
		$url  = "通知的url请求";
		$oCurl = curl_init();
		curl_setopt($oCurl, CURLOPT_URL, $url);
		curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
		$sContent = curl_exec($oCurl);
		$aStatus = curl_getinfo($oCurl);
		curl_close($oCurl);
		if(intval($aStatus["http_code"])==200){
			return $sContent;
		}else{
			return false;
		}
	}
	
}
