<?php

class RightPanel {
    static function buildTitle(Layout $layout, $titleText, $textsize = 1.9) {
        $font = $layout->theme->headerFontStyle;
        $panelW = $layout->geometry->panelWidth;

        return XmlTag::label(1, -1, $panelW - 2, 3, $font . $titleText, null, array('textsize' => $textsize));
    }

    static function buildTitle2(Layout $layout, $titleText) {
        $font = "\$010\$o";
        $panelW = $layout->geometry->panelWidth;

        return XmlTag::label(1, 3, $panelW - 2, 3, $font . $titleText, null, array('textsize' => 1.5));
    }

    private static function build(UmPanelRenderContext $ctx) {
        $panelW = $ctx->layout->geometry->panelWidth;
        $panelH = $ctx->layout->geometry->panelHeight;

        $panelBgQuad = XmlTag::quad(0, 0, $panelW, $panelH, $ctx->layout->theme->panelBackgroundColor);
        $panelBody = self::buildRightPanelBodyXml($ctx);

        return array(
            'panelBgQuad' => $panelBgQuad,
            'panelBody' => $panelBody,
        );
    }

    private static function buildRightPanelBodyXml(UmPanelRenderContext $ctx) {
        switch ($ctx->activeTabAction) {
            case UmPanelKeys::ACT_TAB_SCHEDULE:
                return SchedulePanelBuilder::schedule($ctx->layout, $ctx->umConfig);

            case UmPanelKeys::ACT_TAB_RULES:
                return RulesPanelBuilder::build($ctx->login, $ctx->layout, $ctx->umConfig, $ctx->umState);

            case UmPanelKeys::ACT_TAB_INFORMATION:
                return InformationPanelBuilder::getInformationPanel($ctx->layout);

            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                return QualificationPanelBuilder::build($ctx->login, $ctx->layout, $ctx->umConfig, $ctx->umState);

            case UmPanelKeys::ACT_TAB_PRIZE:
                return '';

            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
            default:
                return PlayerRacesPanel::build($ctx->login, $ctx->selectedPlayerForLogin, $ctx->layout, $ctx->mlAct);
        }
    }

}