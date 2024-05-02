@component('mail::message')
# Congratulations! Your Copy Trading Subscription is Confirmed!

Dear **{{ $subscription->user->name }}**,

We are pleased to confirm that your copy trading request has been successfully executed. Thank you for choosing us as your forex broker. We are committed to providing you with an excellent trading experience.

- **Name**: {{ $subscription->master->tradingUser->name }}
- **Master Metatrader 5 No**: {{ $subscription->master_meta_login }}
- **Join Amount**: {{ $subscription->meta_balance }}
- **Date and Time**: {{ $subscription->approval_date }}

We highly value your trading needs and ensure that all trades are executed promptly and accurately. Our team continues to monitor the market and provide you with the best trading opportunities.

If you have any questions or need further assistance, please feel free to contact our customer service team. Our staff is committed to assisting you and ensuring that your trading experience is optimal.

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

# 恭喜！您的复制交易订阅已确认！

尊敬的**{{ $subscription->user->name }}**,

我们怀着喜悦的心情向您确认,您的跟单交易已成功执行。感谢您选择我们作为您的外汇经纪商,我们致力于为您提供卓越的交易体验。

- **名称**: {{ $subscription->master->tradingUser->name }}
- **主账户Metatrader 5编号**: {{ $subscription->master_meta_login }}
- **加入金额**: {{ $subscription->meta_balance }}
- **日期和时间**: {{ $subscription->approval_date }}

我们高度重视您的交易需求,并确保所有交易都得到及时且准确的执行。我们的团队将持续监控市场并为您提供最佳的交易机会。

如果您有任何疑问或需要进一步协助,请随时联系我们的客户服务团队。我们的工作人员将竭诚为您提供帮助,并确保您的交易体验达到最佳状态。

此致敬礼,
蚂蚁科技团队

@component('mail::subcopy')
"免责声明 - 尽管经过深入研究编制了上述内容,但它纯粹作为信息和教育材料提供。所提供的任何内容都不应被解释为任何形式的投资建议。"
@endcomponent

@component('mail::table')
| 差价合约(CFD)是复杂的金融工具,存在由于杠杆导致的迅速资本损失的重大风险。必须评估您对CFD机制的理解,并评估您是否具备承担重大资本侵蚀风险的财务能力。 |
| ---------------------------------------------------------------------------------------------------------------------- |
| 在进行外汇,差价合约(CFD),点差投注或外汇期权交易之前,请阅读蚂蚁科技的风险披露。外汇/差价合约(CFD),点差投注和外汇期权交易涉及重大损失风险,不适合所有投资者。 |
| 版权所有 © 蚂蚁科技。保留所有权利。 |
@endcomponent

@endcomponent
