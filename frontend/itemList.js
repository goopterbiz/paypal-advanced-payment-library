export function displayItems(items) {
    const container = document.getElementById("item-list");  
    container.style.cssText = `
        max-width: 750px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 20px;
    `;

    // Create product cards
    items.forEach(item => {
        const card = document.createElement('div');
        card.style.cssText = `
            background: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            gap: 20px;
        `;
        
        // Add hover effect
        card.addEventListener('mouseover', () => {
            card.style.transform = 'translateX(5px)';
        });
        card.addEventListener('mouseout', () => {
            card.style.transform = 'translateX(0)';
        });

        // Create image placeholder text
        const imgText = document.createElement('span');
        imgText.textContent = 'Item Image';
        imgText.style.cssText = `
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 20px;
        `;

        // Create content wrapper
        const content = document.createElement('div');
        content.style.cssText = `
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 5px;
        `;

        // Create name element
        const name = document.createElement('h3');
        name.textContent = item.name;
        name.style.cssText = `
            margin: 0;
            font-family: Arial, sans-serif;
            color: #666;
            font-size: 30px;
        `;

        // Create price element
        const price = document.createElement('p');
        price.textContent = `$${item.price.toFixed(2)}`;
        price.style.cssText = `
            margin: 0;
            font-weight: bold;
            font-size: 20px;
        `;

        // Create quantity element
        const quantity = document.createElement('p');
        quantity.textContent = `Qty: 1`;
        quantity.style.cssText = `
            margin: 0;
            color: #666;
            font-size: 24px;
        `;

        // Assemble card
        content.appendChild(name);
        content.appendChild(price);
        card.appendChild(imgText);
        card.appendChild(content);
        card.appendChild(quantity);
        container.appendChild(card);
    });

    // total amount
    const totalAmount = items.reduce((sum, item) => sum + (item.price * 1), 0);
    // Create total element
    const total = document.createElement('div');
    total.style.cssText = `
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: Arial, sans-serif;
    `;

    const totalLabel = document.createElement('span');
    totalLabel.textContent = 'Total:';
    totalLabel.style.cssText = `
        font-size: 18px;
        font-weight: bold;
        color: #333;
    `;

    const totalValue = document.createElement('span');
    totalValue.textContent = `$${totalAmount.toFixed(2)}`;
    totalValue.style.cssText = `
        font-size: 18px;
        font-weight: bold;
    `;

    // Assemble total
    total.appendChild(totalLabel);
    total.appendChild(totalValue);
    container.appendChild(total);
  }