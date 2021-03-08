<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Web3\Web3;
use Web3\Contract;
use Web3\Providers\HttpProvider;
use Web3\RequestManagers\HttpRequestManager;
use Illuminate\Support\Facades\Redis;
use App\Jsonrpc\Eth;

class TestController extends Controller
{
    //发布erc20
    public function erc20Contract()
    {
        //获取合同的ABI
        $abi = config('erc.Erc20Abi');
        //获取合同的Bytecode
        $Bytecode = config('erc.Erc20Bytecode');
        //请求钱包的过期时间（秒）
        $timeout = 60;
        //连接钱包
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('app.eth'), $timeout)));
        //合同
        $contract = new Contract($web3->provider, $abi);
        //用哪个账号发布
        $fromAccount = '0x9b8ddadf75b2831b30356bc1a5010fe77765b83d';
        //账号的密码
        $password = 'vd!LiedNJ9DkGRpA';
        //发给哪个账号
        $toAccount = '0x3138E4972c9ACd112E5514286B76A58442981f0B';
        //解锁
        $a = $this->unlockAccount($fromAccount, $password);
        dd($a);
        if (!$a) {
            return '解锁失败';
        }
        //发布合同
        $contract->bytecode($Bytecode)->new($toAccount, [
            'from' => $fromAccount,
            'gas' => 4712388
        ], function ($err, $result) {
            //失败
            if ($err !== null) {
                Redis::set('hash', 0);
            }
            //成功
            if ($result) {
                Redis::set('hash', $result);
            }
        });
        //因为它是异步的，我控制器中拿不到他的回调，我就把结果存入Redis里面了。
        $hash = Redis::get('hash');
        Redis::del('hash');
        //将账号锁住
        $this->lockAccount($fromAccount);
        //返回hash
        return $hash;
    }
    //发布erc721
    public function erc721Contract()
    {
        //获取erc721的ABI
        $abi = config('erc.Erc721Abi');
        //获取erc721的Bytecode
        $Bytecode = config('erc.Erc721Bytecode');
        $timeout = 60;
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('app.eth'), $timeout)));
        $contract = new Contract($web3->provider, $abi);
        $fromAccount = '0xb24bae98610c454e4e2e3e8711d22af3a3155db0';
        $password = 'vd!LiedNJ9DkGRpA';
        //$toAccount = '0xaba10bfe13e4e3bac8babacd4a2a082152fe1313';
        $a = $this->unlockAccount($fromAccount, $password);
        if (!$a) {
            return '解锁失败';
        }
        //发布合同
        $contract->bytecode($Bytecode)->new([
            'from' => $fromAccount,
            'gas' => 4712388
        ], function ($err, $result) {
            if ($err !== null) {
                Redis::set('hash', 0);
            }
            if ($result) {
                Redis::set('hash', $result);
            }
        });
        $hash = Redis::get('hash');
        Redis::del('hash');
        return $hash;
    }

    //解锁
    public function unlockAccount($fromAccount, $password)
    {
        $timeout = 60;
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('app.eth'), $timeout)));
        $personal = $web3->personal;
        $personal->batch(true);
        //解锁账户
        $personal->unlockAccount($fromAccount, $password, 60);
        //发送获取回调
        $personal->provider->execute(function ($err, $result) use ($fromAccount) {
            //将回调结果存入Redis
            if ($err !== null) {
                Redis::set($fromAccount, 0);
            }
            if ($result) {
                Redis::set($fromAccount, $result[0]);
            }
        });
        //返回解锁结果
        $info = Redis::get($fromAccount);
        return $info;
    }

    //锁住账户
    public function lockAccount($fromAccount)
    {
        $timeout = 60;
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('app.eth'), $timeout)));
        $personal = $web3->personal;
        $personal->batch(true);
        $personal->lockAccount($fromAccount);
        $personal->provider->execute(function ($err, $result) use ($fromAccount) {
            if ($err !== null) {

            }
            if ($result) {
                Redis::del($fromAccount);
            }
        });

    }
    //调用方法call 查询合同的所有者
    public function owner(){
        $abi = config('erc.Erc721Abi');
        $timeout = 60;
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('app.eth'), $timeout)));
        $contract = new Contract($web3->provider, $abi);
        $contractAddress='0x49cbbff45201f887579d0ed5a24917a0a8371d27';
        //调用call('方法','参数','回调')查询
        $contract->at($contractAddress)->call('owner', '', function ($err, $result){
            dd($err,$result);
        });
    }
    //调用方法send 锻造一个ID
    public function mint(Request $request){
        $tokenid=$request->tokenid;
        $abi = config('erc.Erc721Abi');
        $timeout = 60;
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('app.eth'), $timeout)));
        $contract = new Contract($web3->provider, $abi);
        $contractAddress='0x8fff0FfF228424b5907877a4f75E42296933a0AD';
        $fromAccount = '0xb24bae98610c454e4e2e3e8711d22af3a3155db0';
        $password = 'vd!LiedNJ9DkGRpA';
        $a = $this->unlockAccount($fromAccount, $password);
        if (!$a) {
            return '解锁失败';
        }
        //调用send('方法','参数','回调')锻造一个币
        $contract->at($contractAddress)->send('mint', '0x3138E4972c9ACd112E5514286B76A58442981f0B',$tokenid,'1111111',[
            'from' => $fromAccount,
        ], function ($err, $result){
            dd($err,$result);
        });
    }
    //删除id
    public function burn(Request $request){
        $tokenid=$request->tokenid;
        $abi = config('erc.Erc721Abi');
        $timeout = 60;
        $web3 = new Web3(new HttpProvider(new HttpRequestManager(config('app.eth'), $timeout)));
        $contract = new Contract($web3->provider, $abi);
        $contractAddress='0x8fff0FfF228424b5907877a4f75E42296933a0AD';
        $fromAccount = '0xb24bae98610c454e4e2e3e8711d22af3a3155db0';
        $password = 'vd!LiedNJ9DkGRpA';
        $a = $this->unlockAccount($fromAccount, $password);
        if (!$a) {
            return '解锁失败';
        }
        //调用send('方法','参数','回调')锻造一个币
        $contract->at($contractAddress)->send('burn',$tokenid,[
            'from' => $fromAccount,
        ], function ($err, $result){
            dd($err,$result);
        });
    }

    //创建账号
    public function getaddress(){
        $gethrpc=new Eth(config('app.eth'));//测试网络
        $result=$gethrpc->personal_newAccount('vd!LiedNJ9DkGRpA');
        dd($result);
    }

}
