document.querySelectorAll('.inquiry-form').forEach(form => {
    let key=crypto.randomUUID();
    const locale=form.dataset.locale||'zh-hans';
    const messages={
        'zh-hans':['请至少填写邮箱或联系电话。','正在保存，请稍候…','暂时无法提交，请检查内容后重试。'],
        'zh-hant':['請至少填寫信箱或聯絡電話。','正在儲存，請稍候…','暫時無法提交，請檢查內容後重試。'],
        en:['Please provide email or phone.','Saving…','Unable to submit. Check the fields and try again.'],
        fr:['Indiquez un e-mail ou un téléphone.','Enregistrement…','Envoi impossible. Vérifiez les champs et réessayez.'],
        de:['Bitte E-Mail oder Telefonnummer angeben.','Wird gespeichert…','Senden fehlgeschlagen. Angaben prüfen und erneut versuchen.'],
        it:['Inserisci e-mail o telefono.','Salvataggio…','Invio non riuscito. Controlla i campi e riprova.'],
        es:['Indica correo o teléfono.','Guardando…','No se pudo enviar. Revisa los campos e inténtalo de nuevo.']
    };
    const text=messages[locale]||messages.en;
    const params=new URLSearchParams(location.search);
    const country=params.get('country');
    if(country&&[...form.elements.country.options].some(option=>option.value===country))form.elements.country.value=country;
    const services={'菜单':'print','点餐':'ordering','设备':'equipment','print':'print','ordering':'ordering','equipment':'equipment','other':'other'};
    if(services[params.get('service')])form.elements.service.value=services[params.get('service')];
    form.addEventListener('submit',async event=>{
        event.preventDefault();
        const button=form.querySelector('button[type=submit]'),result=form.querySelector('.form-result');
        const values=Object.fromEntries(new FormData(form));values.locale=locale;values.source_url=location.origin+location.pathname;
        if(!values.email&&!values.phone){result.textContent=text[0];return;}
        button.disabled=true;result.textContent=text[1];
        try{
            const response=await fetch(form.dataset.endpoint,{method:'POST',headers:{'Content-Type':'application/json','Idempotency-Key':key},body:JSON.stringify(values)});
            const data=await response.json();
            if(!response.ok){if(response.status===409)key=crypto.randomUUID();throw new Error(text[2]);}
            result.textContent=`${form.dataset.saved}: ${data.reference}. ${form.dataset.savedNote}`;
            // Preserve the product/source context and selections for a follow-up enquiry.
            for(const name of ['name','email','phone','message'])form.elements[name].value='';form.elements.consent.checked=false;key=crypto.randomUUID();
        }catch(error){result.textContent=error.message||text[2];}finally{button.disabled=false;}
    });
});
