@component('mail::message')
# KYC Approval Status

Dear **{{ $user->name }}**,

Your KYC has been approved.

We are pleased to inform you that your KYC (Know Your Customer) documentation has been successfully reviewed and approved. This crucial step ensures compliance with regulatory requirements and allows us to provide you with an enhanced and secure service.

Now that your KYC has been approved, you can proceed with confidence to explore the full range of services we offer. If you have any questions or require further assistance, our dedicated support team is ready to help.

Best Regards,  
Lucky Ant Trading Team

@component('mail::subcopy')
"Disclaimer - Despite thorough research to compile the above content, it serves purely as informational and educational material. None of the content provided should be construed as investment advice in any form."
@endcomponent

@component('mail::table')
| CFD\'s represent intricate financial instruments and pose a substantial risk of rapid capital loss, primarily attributable to leverage. It is imperative to assess your comprehension of CFD mechanics and evaluate whether you possess the financial capacity to assume the considerable risk of capital erosion. |
| ---------------------------------------------------------------------------------------------------------------------- |
| Read Lucky Ant Trading risk disclosure before trading forex, CFD\'s, Spread - betting or FX Options. |
| Forex / CFD\'s, Spread - betting and FX Options trading involves substantial risk of loss and is not suitable for all investors. |
| Copyright © Lucky Ant Trading. All rights reserved. |
@endcomponent

# KYC 审批状态

尊敬的{{ $user->name }},

您的KYC已经获得批准。

我们很高兴地通知您,您的KYC(了解客户身份)文件已经成功审核并批准。这一关键步骤确保我们遵守监管要求,并使我们能够为您提供更强化和安全的服务。

既然您的KYC已经获得批准,您可以放心地继续探索我们提供的全部服务。如果您有任何问题或需要进一步的帮助,我们专门的支持团队已经准备好提供帮助。

此致敬礼,
蚂蚁科技团队

@component('mail::subcopy')
"免责声明 - 尽管经过深入研究编制了上述内容,但它纯粹作为信息和教育材料提供。所提供的任何内容都不应被解释为任何形式的投资建议。"
@endcomponent

@component('mail::table')
| 差价合约(CFDs)是复杂的金融工具,存在由于杠杆导致的迅速资本损失的重大风险。必须评估您对CFDs机制的理解,并评估您是否具备承担重大资本侵蚀风险的财务能力。 |
| ---------------------------------------------------------------------------------------------------------------------- |
| 在进行外汇,差价合约(CFDs),点差投注或外汇期权交易之前,请阅读蚂蚁科技的风险披露。外汇/差价合约(CFDs),点差投注和外汇期权交易涉及重大损失风险,不适合所有投资者。 |
| 版权所有 © 蚂蚁科技。保留所有权利。 |
@endcomponent

@endcomponent
