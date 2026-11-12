document.querySelectorAll('.inquiry-form').forEach(form => {
 let key=crypto.randomUUID();
 form.addEventListener('submit', async event => {
  event.preventDefault(); const button=form.querySelector('button[type=submit]');
  const result=form.querySelector('.form-result'); const values=Object.fromEntries(new FormData(form)); values.locale=form.dataset.locale||'zh-hans';
  const zh=values.locale.startsWith('zh');
  if(!values.email && !values.phone){result.textContent=zh?'请至少填写邮箱或联系电话。':'Please provide email or phone.';return;}
  button.disabled=true;result.textContent=zh?'正在保存，请稍候…':'Saving…';
  try {
   const response=await fetch(form.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json','Idempotency-Key':key},body:JSON.stringify(values)});
   const data=await response.json();
   if(!response.ok){if(response.status===409)key=crypto.randomUUID();throw new Error(zh?(data.message||'无法提交，请稍后重试。'):('Unable to submit ('+(data.code||response.status)+'). Please check the fields or try again.'));}
   result.textContent=zh?`需求已保存，编号：${data.reference}。当前为本地测试，未发送外部通知。`:`Saved: ${data.reference}. Local test only; no external notification sent.`;
   form.reset();key=crypto.randomUUID();
  }catch(error){result.textContent=error.message;}
  finally{button.disabled=false;}
 });
});
