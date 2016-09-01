//
//  HymnFlowLayoutWithAnimations.swift
//  Hymnal
//
//  Created by Jeremy Olson on 8/31/16.
//  Copyright © 2016 Jeremy Olson. All rights reserved.
//

import UIKit

class HymnFlowLayoutWithAnimations: UICollectionViewFlowLayout {
    
    var currentCellPath: NSIndexPath?
    var currentCellCenter: CGPoint?
    var currentCellScale: CGFloat?
    
    func setCurrentCellScale(scale: CGFloat)
    {
        currentCellScale = scale
        self.invalidateLayout()
    }
    
    func setCurrentCellCenter(origin: CGPoint)
    {
        currentCellCenter = origin
        self.invalidateLayout()
    }
    
    override func layoutAttributesForItem(at indexPath: IndexPath) -> UICollectionViewLayoutAttributes? {
            
            let attributes =
                super.layoutAttributesForItem(at: indexPath as IndexPath)
        
            self.modifyLayoutAttributes(layoutattributes: attributes!)
            return attributes
    }
    
    override func layoutAttributesForElements(in rect: CGRect) -> [UICollectionViewLayoutAttributes]? {
        let allAttributesInRect =
            super.layoutAttributesForElements(in: rect)
        
        for cellAttributes in allAttributesInRect! {
            self.modifyLayoutAttributes(layoutattributes: cellAttributes)
        }
        return allAttributesInRect!
    }
    
    func modifyLayoutAttributes(layoutattributes:
        UICollectionViewLayoutAttributes) {
        
        if layoutattributes.indexPath == currentCellPath as! IndexPath {
            layoutattributes.transform3D =
                CATransform3DMakeScale(currentCellScale!,
                                       currentCellScale!, 1.0)
            layoutattributes.center = currentCellCenter!
            layoutattributes.zIndex = 1
        }
    }

    

}
