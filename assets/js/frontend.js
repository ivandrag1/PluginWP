(function(){
	function pollStatus(container){
		const url = container.dataset.url;
		const reference = container.dataset.reference;
		const token = container.dataset.token;
		const timeoutMessage = container.querySelector('.pv-timeout-message');
		const message = container.querySelector('.pv-message');
		let attempts = 0;
		const maxAttempts = 12;

		const interval = window.setInterval(function(){
			attempts += 1;
			fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({reference:reference,token:token})})
				.then(function(response){return response.json();})
				.then(function(data){
					if(data.status === 'paid'){
						window.clearInterval(interval);
						window.location.reload();
						return;
					}
					if(data.status === 'forbidden'){
						window.clearInterval(interval);
						if(message){
							message.textContent = data.message || '';
						}
					}
				})
				.catch(function(){
					if(attempts >= maxAttempts){
						window.clearInterval(interval);
						if(timeoutMessage){timeoutMessage.hidden = false;}
					}
				});

			if(attempts >= maxAttempts){
				window.clearInterval(interval);
				if(timeoutMessage){timeoutMessage.hidden = false;}
			}
		},5000);
	}

	document.addEventListener('DOMContentLoaded',function(){
		document.querySelectorAll('[data-pv-status]').forEach(pollStatus);
	});
})();
