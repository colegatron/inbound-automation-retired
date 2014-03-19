<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html;" charset="UTF-8" />
<style type="text/css">
  html {
    background: #EEEDED;
  }
</style>
</head>
<body style="margin: 0px; background-color: #FFFFFF; font-family: Helvetica, Arial, sans-serif; font-size:12px;" text="#444444" bgcolor="#FFFFFF" link="#21759B" alink="#21759B" vlink="#21759B" marginheight="0" topmargin="0" marginwidth="0" leftmargin="0">

<table cellpadding="0" width="600" bgcolor="#FFFFFF" cellspacing="0" border="0" align="center" style="width:100%!important;line-height:100%!important;border-collapse:collapse;margin-top:0;margin-right:0;margin-bottom:0;margin-left:0;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
  <tbody><tr>
    <td valign="top" height="20">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top">
      <table cellpadding="0" bgcolor="#ffffff" cellspacing="0" border="0" align="center" style="border-collapse:collapse;width:600px;font-size:13px;line-height:20px;color:#545454;font-family:Arial,sans-serif;border-radius:3px;margin-top:0;margin-right:auto;margin-bottom:0;margin-left:auto">
  <tbody><tr>
    <td valign="top">
        <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;width:100%;border-radius:3px 3px 0 0;font-size:1px;line-height:3px;height:3px;border-top-color:#0298e3;border-right-color:#0298e3;border-bottom-color:#0298e3;border-left-color:#0298e3;border-top-style:solid;border-right-style:solid;border-bottom-style:solid;border-left-style:solid;border-top-width:1px;border-right-width:1px;border-bottom-width:1px;border-left-width:1px">
          <tbody><tr>
            <td valign="top" style="font-family:Arial,sans-serif;background-color:#5ab8e7;border-top-width:1px;border-top-color:#8ccae9;border-top-style:solid" bgcolor="#5ab8e7">&nbsp;</td>
          </tr>
        </tbody></table>
      <table cellpadding="0" cellspacing="0" border="0" style="border-collapse:separate;width:600px;border-radius:0 0 3px 3px;border-top-color:#8c8c8c;border-right-color:#8c8c8c;border-bottom-color:#8c8c8c;border-left-color:#8c8c8c;border-top-style:solid;border-right-style:solid;border-bottom-style:solid;border-left-style:solid;border-top-width:0;border-right-width:1px;border-bottom-width:1px;border-left-width:1px">
        <tbody><tr>
          <td valign="top" style="font-size:13px;line-height:20px;color:#545454;font-family:Arial,sans-serif;border-radius:0 0 3px 3px;padding-top:3px;padding-right:30px;padding-bottom:15px;padding-left:30px">

  <h1 style="margin-top:20px;margin-right:0;margin-bottom:20px;margin-left:0; font-size:28px; line-height: 28px; color:#000;">New Lead on {{form-name}}</h1>
  <p style="margin-top:20px;margin-right:0;margin-bottom:20px;margin-left:0">There is a new lead that just converted on <strong>{{date-time}}</strong> from page: {{current-page-url}} {{redirect-message}}</p>

<!-- NEW TABLE -->
<table class="heavyTable" style="width: 100%;
    max-width: 600px;
    border-collapse: collapse;
    border: 1px solid #cccccc;
    background: white;
   margin-bottom: 20px;">
   <tbody>
     <tr style="background: #3A9FD1; height: 54px; font-weight: lighter; color: #fff;border: 1px solid #3A9FD1;text-align: left; padding-left: 10px;">
             <td  align="left" width="600" style="-webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px; color: #fff; font-weight: bold; text-decoration: none; font-family: Helvetica, Arial, sans-serif; display: block;">
              <h1 style="font-size: 30px; display: inline-block;margin-top: 15px;margin-left: 10px; margin-bottom: 0px; letter-spacing: 0px; word-spacing: 0px; font-weight: 300;">Lead Information</h1>
              <div style="float:right; margin-top: 5px; margin-right: 15px;"><!--[if mso]>
                <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{admin-url}}edit.php?post_type=wp-lead&lead-email-redirect={{lead-email}}" style="height:40px;v-text-anchor:middle;width:130px;font-size:18px;" arcsize="10%" stroke="f" fillcolor="#ffffff">
                  <w:anchorlock/>
                  <center>
                <![endif]-->
                    <a href="'.admin_url( 'edit.php?post_type=wp-lead&lead-email-redirect=' . $form_email ).'"
              style="background-color:#ffffff;border-radius:4px;color:#3A9FD1;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:130px;-webkit-text-size-adjust:none;">View Lead</a>
                <!--[if mso]>
                  </center>
                </v:roundrect>
              <![endif]-->
              </div>
             </td>
     </tr>
	 
	 <!-- LOOP THROUGH POST PARAMS -->
	 {%php for key,value in form_data %) 
	 <tr style="border-bottom: 1px solid #cccccc;">
		<td width="600" style="border-right: 1px solid #cccccc; padding: 10px; padding-bottom: 5px;">
		<div style="padding-left:5px; display:inline-block; padding-bottom: 5px; font-size: 16px; color:#555;">
     	<strong>{{form_data.key}}</strong></div>
     	<div style="padding-left:5px; display:inline-block; font-size: 14px; color:#000;"><span style='color:#949494; font-size: 10px;'>{{form_data.value}}</span></div>
     	</td>
     	</tr>
	 {%php endfor %}
	 
	 <!-- END LOOP -->
	 
	 <!-- IF CHAR COUNT OVER 50 make label display block -->

   </tbody>
 </table>
 <!-- END NEW TABLE -->
<!-- Start 3 col -->
<table style="margin-bottom: 20px; border: 1px solid #cccccc; border-collapse: collapse;" width="100%" border="1" BORDERWIDTH="1" BORDERCOLOR="CCCCCC" cellspacing="0" cellpadding="5" align="left" valign="top" borderspacing="0" >

<tbody valign="top">
 <tr valign="top" border="0">
  <td width="160" height="50" align="center" valign="top" border="0">
     <h3 style="color:#2e2e2e;font-size:15px;"><a style="text-decoration: none;" href="{{admin-url}}edit.php?post_type=wp-lead&lead-email-redirect={{lead-email}}&tab=tabs-wpleads_lead_tab_conversions">View Lead Activity</a></h3>
  </td>

  <td width="160" height="50" align="center" valign="top" border="0">
     <h3 style="color:#2e2e2e;font-size:15px;"><a style="text-decoration: none;" href="{{admin-url}}edit.php?post_type=wp-lead&lead-email-redirect='{{lead-email}}&scroll-to=wplead_metabox_conversion">Pages Viewed</a></h3>
  </td>

 <td width="160" height="50" align="center" valign="top" border="0">
    <h3 style="color:#2e2e2e;font-size:15px;"><a style="text-decoration: none;" href="{{admin-url}}edit.php?post_type=wp-lead&lead-email-redirect={{lead-email}}&tab=tabs-wpleads_lead_tab_raw_form_data'">View Form Data</a></h3>
 </td>
 </tr>
</tbody></table>
<!-- end 3 col -->
 <!-- Start half/half -->
 <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:10px;">
     <tbody><tr>
      <td align="center" width="250" height="30" cellpadding="5">
         <div><!--[if mso]>
           <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="'.admin_url( 'edit.php?post_type=wp-lead&lead-email-redirect=' . $form_email ).'" style="height:40px;v-text-anchor:middle;width:250px;" arcsize="10%" strokecolor="#7490af" fillcolor="#3A9FD1">
             <w:anchorlock/>
             <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">View Lead</center>
           </v:roundrect>
         <![endif]--><a href="{{admin-url}}edit.php?post_type=wp-lead&lead-email-redirect={{lead-email}}"
         style="background-color:#3A9FD1;border:1px solid #7490af;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:250px;-webkit-text-size-adjust:none;mso-hide:all;" title="View the full Lead details in WordPress">View Full Lead Details</a>
       </div>
      </td>

       <td align="center" width="250" height="30" cellpadding="5">
         <div><!--[if mso]>
           <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="mailto:'.$form_email.'?subject=RE: '.$form_data['inbound_form_name'].'&body=Thanks for filling out our form." style="height:40px;v-text-anchor:middle;width:250px;" arcsize="10%" strokecolor="#558939" fillcolor="#59b329">
             <w:anchorlock/>
             <center style="color:#ffffff;font-family:sans-serif;font-size:13px;font-weight:bold;">Reply to Lead Now</center>
           </v:roundrect>
         <![endif]--><a href="mailto:{{lead-email}}?subject=RE:{{form-name}}&body=Thanks for filling out our form on {{current-page-url}}"
         style="background-color:#59b329;border:1px solid #558939;border-radius:4px;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:18px;font-weight:bold;line-height:40px;text-align:center;text-decoration:none;width:250px;-webkit-text-size-adjust:none;mso-hide:all;" title="Email This Lead now">Reply to Lead Now</a></div>

       </td>
     </tr>
   </tbody>
 </table>
<!-- End half/half -->

          </td>
        </tr>
      </tbody></table>
    </td>
  </tr>
</tbody></table>
<table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse:collapse;width:600px;font-size:13px;line-height:20px;color:#545454;font-family:Arial,sans-serif;margin-top:0;margin-right:auto;margin-bottom:0;margin-left:auto">
  <tbody><tr>
    <td valign="top" width="30" style="color:#272727">&nbsp;</td>
    <td valign="top" height="18" style="height:18px;color:#272727"></td>
      <td style="color:#272727">&nbsp;</td>
    <td style="color:#545454;text-align:right" align="right">&nbsp;</td>
    <td valign="middle" width="30" style="color:#272727">&nbsp;</td>
  </tr>
  <tr>
    <td valign="middle" width="30" style="color:#272727">&nbsp;</td>
      <td width="50" height="40" valign="middle" align="left" style="color:#272727">
		<img src="'. INBOUND_FORMS . 'images/inbound-email.png" height="40" width="40" alt=" " style="outline:none;text-decoration:none;max-width:100%;display:block;width:40px;min-height:40px;border-radius:20px">
      </td>
    <td style="color:#272727">
      <b>Leads</b>
       from Inbound Now
    </td>
    <td valign="middle" align="left" style="color:#545454;text-align:right">'.$data_time.'</td>
    <td valign="middle" width="30" style="color:#272727">&nbsp;</td>
  </tr>
  <tr>
    <td valign="top" height="6" style="color:#272727;line-height:1px">&nbsp;</td>
    <td style="color:#272727;line-height:1px">&nbsp;</td>
      <td style="color:#272727;line-height:1px">&nbsp;</td>
    <td style="color:#545454;text-align:right;line-height:1px" align="right">&nbsp;</td>
    <td valign="middle" width="30" style="color:#272727;line-height:1px">&nbsp;</td>
  </tr>
</tbody></table>

      <table cellpadding="0" cellspacing="0" border="0" align="center" style="border-collapse:collapse;width:600px">
        <tbody><tr>
          <td valign="top" style="color:#b1b1b1;font-size:11px;line-height:16px;font-family:Arial,sans-serif;text-align:center" align="center">
            <p style="margin-top:1em;margin-right:0;margin-bottom:1em;margin-left:0"></p>
          </td>
        </tr>
      </tbody></table>
    </td>
  </tr>
  <tr>
    <td valign="top" height="20">&nbsp;</td>
  </tr>
</tbody></table>
</body>