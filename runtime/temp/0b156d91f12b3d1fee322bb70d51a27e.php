<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:89:"/Users/lupengcheng/Downloads/php/cx/public/../application/index/view/login/updatepwd.html";i:1534819314;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码</title>
    <meta name="keywords" content="H+后台主题,后台bootstrap框架,会员中心主题,后台HTML,响应式后台">
    <meta name="description" content="H+是一个完全响应式，基于Bootstrap3最新版本开发的扁平化主题，她采用了主流的左右两栏式布局，使用了Html5+CSS3等现代技术">
    <link rel="shortcut icon" href="favicon.ico">
    <link href="/static/hplus/css/bootstrap.min14ed.css?v=3.3.6" rel="stylesheet">
    <link href="/static/hplus/css/font-awesome.min93e3.css?v=4.4.0" rel="stylesheet">
    <link href="/static/hplus/css/plugins/iCheck/custom.css" rel="stylesheet">
    <link href="/static/hplus/css/animate.min.css" rel="stylesheet">
    <link href="/static/hplus/css/style.min862f.css?v=" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="/static/hplus/css/demo/style.css?v=4" />
</head>
<style type="text/css">
        .shanpic{
            width: 220px;
            height: 116px;
            border: 1px solid #999;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .shanpic2{
            width: 100px;
            height: 100px;
            border: 1px solid #999;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        #picFile{
            position: absolute;
            top:0;
            left:0;
            width: 100%;
            height: 100%;
            opacity: 0;
            z-index: 20;
            cursor: pointer;
            display: block;
        }
       .shanpic2 .shangchuan_h{
     
            font-size: 8rem;
            position: absolute;
            top: -11px;
            left: 24px;
            font-weight: normal;
            z-index: 10;
            margin: auto;
            overflow: hidden;
        }
         .shanpic .shangchuan_h{
      
            font-size: 8rem;
            position: absolute;
            top: -2px;
            left: 82px;
            font-weight: normal;
            z-index: 10;
            margin: auto;
            overflow: hidden;
        }
        .tuimg,.tuimg2{
            width: 100%;
            height:auto;
            position: absolute;
            top:0;
            left:0;
            z-index: 16;
            display: block;
        }

        .htmleaf-container,.htmleaf-container2{
                top: 10px;
            }
            .gunlun {
                bottom: 68px;
            }
            .nav_imgs{
              width: 100%;
              height: 100%;
              position:absolute;
              top:0;
              left:0;
              z-index: 99;
              opacity: 0;
            }


</style>

<body class="gray-bg">
    <div class="wrapper wrapper-content animated fadeInRight">
        <div class="row">
         <div class="col-sm-2"></div>
            <div class="col-sm-12">
                <div class="ibox float-e-margins">
                   <div class="input-group">
                        <div class="page-container">
                            <div class="row cl" style="margin-top:9%; ">
                                <label class="form-label col-xs-4 col-sm-4">原密码：</label>
                                <div class="formControls col-xs-8 col-sm-7">
                                <input type="text" name="" class="oldpwd" style="padding: 3%;width: 100%;">

                                </div>
                                </div>  
                                    <div class="row cl" style="margin-top:9%; ">
                                    <label class="form-label col-xs-4 col-sm-4">新密码：</label>
                                    <div class="formControls col-xs-8 col-sm-7">
                                    <input type="text" name="" class="newpwd" style="padding: 3%;width: 100%;">
                                    </div>
                                </div>  
                                    <div class="row cl" style="margin-top:9%; ">
                                    <label class="form-label col-xs-4 col-sm-4">确认密码：</label>
                                    <div class="formControls col-xs-8 col-sm-7">
                                    <input type="text" name="" class="newpwd2" style="padding: 3%;width: 100%;">
                                    </div>
                                </div>  

                            </div>
                          </div>
                        </div>
                            <div class="hr-line-dashed"></div>
                            <div class="form-group">
                                <div class="col-sm-4 col-sm-offset-1">
                                    <button class="btn btn-primary" onclick="update_pwd()" type="button">修改</button>
                                </div>
                            </div>
                    </div>
                </div>
            </div>
          
    <script src="/static/hplus/js/jquery.min.js?v=2.1.4"></script>
    <script src="/static/hplus/js/bootstrap.min.js?v=3.3.6"></script>
    <script src="/static/hplus/js/content.min.js?v=1.0.0"></script>
    <script src="/static/hplus/js/plugins/iCheck/icheck.min.js"></script>
    <script src="/static/hplus/jquery.min.js?v=4" type="text/javascript"></script>
    <script src="/static/hplus/iscroll-zoom.js?v=4" type="text/javascript"></script>
    <script src="/static/hplus/hammer.js?v=4" type="text/javascript"></script>
    <script src="/static/hplus/jquery.photoClip.js?v=4" type="text/javascript"></script>
    <script src="/static/hplus/js/demo/layer-demo.min.js"></script>
    <script src="/static/hplus/js/layer/layer.js"></script>
    <script>
   
    //修改密码
    function update_pwd(){
      var oldpwd = $(".oldpwd").val();
      var newpwd = $(".newpwd").val();
      var newpwd2 = $(".newpwd2").val();
      if(oldpwd == ""){
          parent.layer.msg("原密码不能为空！");
          return;
       }else if(newpwd == ""){
          parent.layer.msg("新密码不能为空！");
          return;
       }else if(newpwd2 == ""){
          parent.layer.msg("确认密码不能为空！");
          return;
       }else if(newpwd != newpwd2){
          parent.layer.msg("两次输入的密码不一致");
          return;
       }
        $.ajax({
            url: "<?php echo \think\Url::build('system/do_update'); ?>",
            data: {
                oldpwd:oldpwd,
                newpwd:newpwd,
                newpwd2:newpwd2
            },
            dataType: "json",
            type: "post",
            success: function(data) {
                if(data.code == 1){
                    layer.msg(data.msg, {icon: 1, time: 1500});
                    window.location.href="<?php echo \think\Url::build('login/index'); ?>"
                }else{
                    layer.msg(data.msg, {icon: 1, time: 1500});
                }
            }
        })
    }
    </script>
</body>


<!-- Mirrored from www.zi-han.net/theme/hplus/form_basic.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 20 Jan 2016 14:19:15 GMT -->
</html>
